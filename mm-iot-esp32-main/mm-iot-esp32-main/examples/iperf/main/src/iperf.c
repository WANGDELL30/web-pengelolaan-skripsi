/*
 * Copyright 2022-2023 Morse Micro
 *
 * SPDX-License-Identifier: Apache-2.0
 */

/**
 * @file
 * @brief Throughput measurement using iperf.
 *
 * The Iperf parameters are specified using the defines in the file. Additional defines in
 * @c mm_app_loadconfig.c and @c mm_app_common.c are used to configure the network stack and WLAN
 * interface.
 *
 * @note It is assumed that you have followed the steps in the @ref GETTING_STARTED guide and are
 * therefore familiar with how to build, flash, and monitor an application using the MM-IoT-SDK
 * framework.
 *
 * This file demonstrates how to run iperf using the Morse Micro WLAN API.
 */


#include <arpa/inet.h>
#include <ctype.h>
#include <endian.h>
#include <errno.h>
#include <stdint.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <netinet/in.h>
#include <sys/socket.h>
#include <sys/time.h>
#include <time.h>
#include <unistd.h>
#include "driver/gpio.h"
#include "driver/i2c.h"
#include "driver/uart.h"
#include "esp_err.h"
#include "esp_event.h"
#include "esp_http_server.h"
#include "mmosal.h"
#include "mmwlan.h"
#include "mmipal.h"
#include "esp_sntp.h"
#include "freertos/FreeRTOS.h"

#include "mmiperf.h"
#include "mm_app_common.h"

/* ------------------------ Configuration options ------------------------ */

/** Iperf configurations. */
enum iperf_type
{
    IPERF_TCP_SERVER,   /**< TCP server (RX) */
    IPERF_UDP_SERVER,    /**< UDP server (RX) */
    IPERF_TCP_CLIENT,   /**< TCP client (TX) */
    IPERF_UDP_CLIENT,   /**< UDP client (TX) */
};

#ifndef IPERF_TYPE
/** Type of iperf instance to start. */
#define IPERF_TYPE                      IPERF_UDP_SERVER
#endif

#ifndef IPERF_AUTOSTART
/**
 * Set ke 0 untuk menonaktifkan iperf server saat boot. Sangat membantu kalau
 * slave hanya dipakai sebagai sumber GPS + CoT lewat HaLow, karena iperf yang
 * standby tetap menyita airtime dan membuat polling HTTP terasa lambat.
 */
#define IPERF_AUTOSTART                 0
#endif

#ifndef IPERF_SERVER_IP
/** IP address of server to connect to when in client mode. */
#define IPERF_SERVER_IP                 "192.168.1.1"
#endif

#ifndef IPERF_TIME_AMOUNT
/**
 * Duration for client transfers specified either in seconds or bytes.
 * If this is negative, it specifies a time in seconds; if positive, it
 * specifies the number of bytes to transmit.
 */
#define IPERF_TIME_AMOUNT               -10
#endif
#ifndef IPERF_SERVER_PORT
/** Specifies the port to listen on in server mode. */
#define IPERF_SERVER_PORT               5001
#endif

#ifndef ATAK_COT_ENABLE
/** Enables periodic CoT unicast messages to ATAK. */
#define ATAK_COT_ENABLE                 1
#endif

#ifndef ATAK_COT_IP
/** ATAK phone/tablet IP address configured in ATAK Management -> Inputs. */
#define ATAK_COT_IP                     "192.168.1.102"
#endif

#ifndef ATAK_COT_PORT
/** ATAK UDP input port. */
#define ATAK_COT_PORT                   6969
#endif

#ifndef ATAK_COT_UID
/** Unique identifier displayed in ATAK. */
#define ATAK_COT_UID                    "SLAVE-HALOW-01"
#endif

#ifndef ATAK_COT_CALLSIGN
/** Callsign displayed in ATAK. */
#define ATAK_COT_CALLSIGN               "Slave-HaLow"
#endif

#ifndef ATAK_COT_TYPE
/** CoT event type. */
#define ATAK_COT_TYPE                   "a-f-G-U-C"
#endif

#ifndef ATAK_COT_HOW
/** CoT "how" field. */
#define ATAK_COT_HOW                    "m-g"
#endif

#ifndef ATAK_COT_LAT
/** Fixed latitude shown in ATAK. Replace with GPS data if available. */
#define ATAK_COT_LAT                    "-6.209650"
#endif

#ifndef ATAK_COT_LON
/** Fixed longitude shown in ATAK. Replace with GPS data if available. */
#define ATAK_COT_LON                    "106.846700"
#endif

#ifndef ATAK_COT_HAE
/** Height above ellipsoid used by the CoT point. */
#define ATAK_COT_HAE                    "0"
#endif

#ifndef ATAK_COT_CE
/** Circular error in meters used by the CoT point. */
#define ATAK_COT_CE                     "5"
#endif

#ifndef ATAK_COT_LE
/** Linear error in meters used by the CoT point. */
#define ATAK_COT_LE                     "5"
#endif

#ifndef ATAK_COT_INTERVAL_MS
/** How often to send a CoT message to ATAK. */
#define ATAK_COT_INTERVAL_MS            5000
#endif

#ifndef ATAK_COT_STALE_SEC
/** How long ATAK should keep the event alive without refresh. */
#define ATAK_COT_STALE_SEC              15
#endif

#ifndef ATAK_COT_REQUIRE_GPS_FIX
/** When enabled, slave CoT is sent only after a live GPS fix is available. */
#define ATAK_COT_REQUIRE_GPS_FIX        0
#endif

#ifndef ATAK_COT_NO_FIX_LOG_INTERVAL_MS
/** Limits how often "waiting for GPS fix" is printed while CoT is paused. */
#define ATAK_COT_NO_FIX_LOG_INTERVAL_MS 5000
#endif

#ifndef ATAK_COT_TASK_STACK_WORDS
/** Task stack size in 32-bit words. */
#define ATAK_COT_TASK_STACK_WORDS       2048
#endif

#ifndef ATAK_COT_SNTP_ENABLE
/** Enables a best-effort SNTP sync before sending CoT. */
#define ATAK_COT_SNTP_ENABLE            1
#endif

#ifndef ATAK_COT_SNTP_SERVER
/** SNTP server used to obtain UTC time for CoT timestamps. */
#define ATAK_COT_SNTP_SERVER            "pool.ntp.org"
#endif

#ifndef ATAK_COT_SNTP_TIMEOUT_MS
/** Maximum time to wait for SNTP before falling back to build time. */
#define ATAK_COT_SNTP_TIMEOUT_MS        10000
#endif

#ifndef ATAK_COT_MIN_VALID_EPOCH
/** January 1, 2024 00:00:00 UTC. */
#define ATAK_COT_MIN_VALID_EPOCH        1704067200UL
#endif

#ifndef ATAK_COT_PAYLOAD_MAX_LEN
/** CoT payload buffer size. */
#define ATAK_COT_PAYLOAD_MAX_LEN        512
#endif

#ifndef ATAK_GPS_ENABLE
/** Enables NEO-7M/NEO-M8N GPS/NMEA position updates over UART with fixed-position fallback. */
#define ATAK_GPS_ENABLE                 1
#endif

#ifndef ATAK_GPS_UART_NUM
/** UART instance used to receive NMEA data from an external GPS. */
#define ATAK_GPS_UART_NUM               UART_NUM_1
#endif

#ifndef ATAK_GPS_UART_BAUD_RATE
/** Default baud rate for common NMEA GPS modules. */
#define ATAK_GPS_UART_BAUD_RATE         9600
#endif

#ifndef ATAK_GPS_UART_TX_PIN
/** Optional ESP TX to GPS RX. Leave GPS RX disconnected if you only need NMEA receive. */
#define ATAK_GPS_UART_TX_PIN            GPIO_NUM_6
#endif

#ifndef ATAK_GPS_UART_RX_PIN
/** Connect GPS module TX to Seeed XIAO D7 / ESP32-S3 GPIO44. */
#define ATAK_GPS_UART_RX_PIN            GPIO_NUM_44
#endif

#ifndef ATAK_GPS_UART_BUF_SIZE
/** UART driver RX buffer size for GPS input. */
#define ATAK_GPS_UART_BUF_SIZE          1024
#endif

#ifndef ATAK_GPS_LINE_MAX_LEN
/** Maximum NMEA line length handled by the parser. */
#define ATAK_GPS_LINE_MAX_LEN           128
#endif

#ifndef ATAK_GPS_FIX_STALE_MS
/** Ignore GPS fixes older than this and fall back to fixed coordinates. */
#define ATAK_GPS_FIX_STALE_MS           15000
#endif

#ifndef ATAK_GPS_TASK_STACK_WORDS
/** Task stack size in 32-bit words for the GPS UART reader. */
#define ATAK_GPS_TASK_STACK_WORDS       2048
#endif

#ifndef BMP180_ENABLE
/** Legacy BMP180 I2C sensor support. Disabled because GPS replaces this sensor input. */
#define BMP180_ENABLE                   0
#endif

#ifndef BMP180_I2C_PORT
/** I2C port used for the BMP180 sensor. */
#define BMP180_I2C_PORT                 I2C_NUM_0
#endif

#ifndef BMP180_I2C_SDA_PIN
/** Connect BMP180 SDA to Seeed XIAO D7 / GPIO44.
 *  GPIO43 is avoided here because UART0 console output is enabled in this project. */
#define BMP180_I2C_SDA_PIN              GPIO_NUM_44
#endif

#ifndef BMP180_I2C_SCL_PIN
/** Connect BMP180 SCL to Seeed XIAO D5 / GPIO6. */
#define BMP180_I2C_SCL_PIN              GPIO_NUM_6
#endif

#ifndef BMP180_I2C_FREQ_HZ
/** Use a conservative I2C clock because the sensor is attached through jumper wiring. */
#define BMP180_I2C_FREQ_HZ              50000
#endif

#ifndef BMP180_SAMPLE_INTERVAL_MS
/** How often BMP180 data is refreshed. */
#define BMP180_SAMPLE_INTERVAL_MS       3000
#endif

#ifndef BMP180_SAMPLE_RETRIES
/** Number of reads used to stabilize one published BMP180 sample. */
#define BMP180_SAMPLE_RETRIES           3
#endif

#ifndef BMP180_TEMP_MIN_DECI_C
/** Reject temperatures below -40.0 C as invalid for this sensor chain. */
#define BMP180_TEMP_MIN_DECI_C          (-400)
#endif

#ifndef BMP180_TEMP_MAX_DECI_C
/** Reject temperatures above 85.0 C as invalid for this sensor chain. */
#define BMP180_TEMP_MAX_DECI_C          850
#endif

#ifndef BMP180_PRESSURE_MIN_PA
/** Reject pressure below 30000 Pa as implausible for this application. */
#define BMP180_PRESSURE_MIN_PA          30000
#endif

#ifndef BMP180_PRESSURE_MAX_PA
/** Reject pressure above 110000 Pa as implausible for this application. */
#define BMP180_PRESSURE_MAX_PA          110000
#endif

#ifndef BMP180_DATA_STALE_MS
/** Ignore BMP180 samples older than this when building CoT remarks. */
#define BMP180_DATA_STALE_MS            10000
#endif

#ifndef BMP180_TASK_STACK_WORDS
/** Task stack size in 32-bit words for the BMP180 reader. */
#define BMP180_TASK_STACK_WORDS         2048
#endif

#ifndef STATUS_WEB_ENABLE
/** Enable a small embedded web UI and JSON API on the ESP32. */
#define STATUS_WEB_ENABLE               1
#endif

#ifndef STATUS_WEB_PORT
/** TCP port used by the embedded HTTP server. */
#define STATUS_WEB_PORT                 80
#endif

#ifndef STATUS_WEB_FIRMWARE_VERSION
/** Visible firmware marker used to verify the flashed binary from the browser. */
#define STATUS_WEB_FIRMWARE_VERSION     "gps-neo-m8n-v1-20260508"
#endif

#ifndef TEXT_MESSAGE_ENABLE
/** Enables a lightweight text message inbox API on the ESP32 slave. */
#define TEXT_MESSAGE_ENABLE             1
#endif

#ifndef TEXT_MESSAGE_MAX_LEN
/** Maximum accepted text message length in bytes. */
#define TEXT_MESSAGE_MAX_LEN            512
#endif

#ifndef TEXT_MESSAGE_SOURCE_MAX_LEN
/** Maximum accepted source label length in bytes. */
#define TEXT_MESSAGE_SOURCE_MAX_LEN     64
#endif

#ifndef TEXT_MESSAGE_RESPONSE_MAX_LEN
/** Response buffer size for message API JSON payloads. */
#define TEXT_MESSAGE_RESPONSE_MAX_LEN   1600
#endif

#ifndef TEXT_MESSAGE_MASTER_URL_MAX_LEN
/** Maximum master inbox URL length accepted by /api/send-master. */
#define TEXT_MESSAGE_MASTER_URL_MAX_LEN 384
#endif

#ifndef TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN
/** Maximum request URL length used when the slave sends a text message to the master inbox. */
#define TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN 1536
#endif

#if BMP180_ENABLE && ATAK_GPS_ENABLE && \
    ((BMP180_I2C_SDA_PIN == ATAK_GPS_UART_RX_PIN) || (BMP180_I2C_SCL_PIN == ATAK_GPS_UART_RX_PIN))
#error "BMP180 I2C pins conflict with the configured GPS UART RX pin."
#endif

/* ------------------------ End of configuration options ------------------------ */

/** Array of power of 10 unit specifiers. */
static const char units[] = {' ', 'K', 'M', 'G', 'T'};

static uint32_t iperf_command_sent_time_ms;
static uint32_t iperf_command_received_time_ms;

#if STATUS_WEB_ENABLE
static httpd_handle_t status_web_server = NULL;
#endif

#if STATUS_WEB_ENABLE && TEXT_MESSAGE_ENABLE
static struct
{
    struct mmosal_mutex *lock;
    uint32_t message_count;
    uint32_t last_received_ms;
    char last_source[TEXT_MESSAGE_SOURCE_MAX_LEN];
    char last_message[TEXT_MESSAGE_MAX_LEN + 1];
} text_message_state = {0};
#endif

#if BMP180_ENABLE
#define BMP180_I2C_ADDR                 0x77
#define BMP180_CHIP_ID_REG              0xD0
#define BMP180_CHIP_ID                  0x55
#define BMP180_CALIB_REG                0xAA
#define BMP180_CONTROL_REG              0xF4
#define BMP180_DATA_REG                 0xF6
#define BMP180_TEMP_CMD                 0x2E
#define BMP180_PRESSURE_CMD             0x34
#define BMP180_OSS                      0
#define BMP180_I2C_TIMEOUT_MS           1000

struct bmp180_calibration
{
    int16_t ac1;
    int16_t ac2;
    int16_t ac3;
    uint16_t ac4;
    uint16_t ac5;
    uint16_t ac6;
    int16_t b1;
    int16_t b2;
    int16_t mb;
    int16_t mc;
    int16_t md;
};

static struct
{
    struct mmosal_mutex *lock;
    struct mmosal_task *task;
    bool detected;
    bool valid;
    esp_err_t last_error;
    int32_t temperature_deci_c;
    int32_t pressure_pa;
    uint32_t last_update_ms;
} bmp180_state = {0};

static uint16_t bmp180_read_u16_be(const uint8_t *data)
{
    return (uint16_t)(((uint16_t)data[0] << 8) | data[1]);
}

static int16_t bmp180_read_s16_be(const uint8_t *data)
{
    return (int16_t)bmp180_read_u16_be(data);
}

static esp_err_t bmp180_i2c_read(uint8_t reg, uint8_t *data, size_t len)
{
    return i2c_master_write_read_device(BMP180_I2C_PORT,
                                        BMP180_I2C_ADDR,
                                        &reg,
                                        1,
                                        data,
                                        len,
                                        pdMS_TO_TICKS(BMP180_I2C_TIMEOUT_MS));
}

static esp_err_t bmp180_i2c_write_u8(uint8_t reg, uint8_t value)
{
    uint8_t data[2] = {reg, value};

    return i2c_master_write_to_device(BMP180_I2C_PORT,
                                      BMP180_I2C_ADDR,
                                      data,
                                      sizeof(data),
                                      pdMS_TO_TICKS(BMP180_I2C_TIMEOUT_MS));
}

static esp_err_t bmp180_probe_address(uint8_t addr)
{
    i2c_cmd_handle_t cmd = i2c_cmd_link_create();
    esp_err_t err;

    if (cmd == NULL)
    {
        return ESP_ERR_NO_MEM;
    }

    err = i2c_master_start(cmd);
    if (err == ESP_OK)
    {
        err = i2c_master_write_byte(cmd, (addr << 1) | I2C_MASTER_WRITE, true);
    }
    if (err == ESP_OK)
    {
        err = i2c_master_stop(cmd);
    }
    if (err == ESP_OK)
    {
        err = i2c_master_cmd_begin(BMP180_I2C_PORT, cmd, pdMS_TO_TICKS(100));
    }

    i2c_cmd_link_delete(cmd);
    return err;
}

static esp_err_t bmp180_init_i2c(void)
{
    i2c_config_t conf = {
        .mode = I2C_MODE_MASTER,
        .sda_io_num = BMP180_I2C_SDA_PIN,
        .scl_io_num = BMP180_I2C_SCL_PIN,
        .sda_pullup_en = GPIO_PULLUP_ENABLE,
        .scl_pullup_en = GPIO_PULLUP_ENABLE,
        .master.clk_speed = BMP180_I2C_FREQ_HZ,
        .clk_flags = 0,
    };
    esp_err_t err;

    err = i2c_param_config(BMP180_I2C_PORT, &conf);
    if (err != ESP_OK)
    {
        return err;
    }

    err = i2c_driver_install(BMP180_I2C_PORT, conf.mode, 0, 0, 0);
    return (err == ESP_ERR_INVALID_STATE) ? ESP_OK : err;
}

static void bmp180_scan_i2c_bus(void)
{
    unsigned found = 0;
    uint8_t addr;

    printf("BMP180 I2C bus check: SDA=GPIO%d level=%d, SCL=GPIO%d level=%d, freq=%dHz\n",
           (int)BMP180_I2C_SDA_PIN,
           gpio_get_level(BMP180_I2C_SDA_PIN),
           (int)BMP180_I2C_SCL_PIN,
           gpio_get_level(BMP180_I2C_SCL_PIN),
           BMP180_I2C_FREQ_HZ);

    for (addr = 0x03; addr < 0x78; addr++)
    {
        if (bmp180_probe_address(addr) == ESP_OK)
        {
            printf("I2C device responded at address 0x%02x\n", addr);
            found++;
        }
    }

    if (found == 0)
    {
        printf("No I2C devices responded on this bus.\n");
    }
}

static esp_err_t bmp180_read_calibration(struct bmp180_calibration *cal)
{
    uint8_t data[22] = {0};
    esp_err_t err;

    if (cal == NULL)
    {
        return ESP_ERR_INVALID_ARG;
    }

    err = bmp180_i2c_read(BMP180_CALIB_REG, data, sizeof(data));
    if (err != ESP_OK)
    {
        return err;
    }

    cal->ac1 = bmp180_read_s16_be(&data[0]);
    cal->ac2 = bmp180_read_s16_be(&data[2]);
    cal->ac3 = bmp180_read_s16_be(&data[4]);
    cal->ac4 = bmp180_read_u16_be(&data[6]);
    cal->ac5 = bmp180_read_u16_be(&data[8]);
    cal->ac6 = bmp180_read_u16_be(&data[10]);
    cal->b1 = bmp180_read_s16_be(&data[12]);
    cal->b2 = bmp180_read_s16_be(&data[14]);
    cal->mb = bmp180_read_s16_be(&data[16]);
    cal->mc = bmp180_read_s16_be(&data[18]);
    cal->md = bmp180_read_s16_be(&data[20]);

    return ESP_OK;
}

static esp_err_t bmp180_read_raw_temperature(int32_t *ut)
{
    uint8_t data[2] = {0};
    esp_err_t err;

    if (ut == NULL)
    {
        return ESP_ERR_INVALID_ARG;
    }

    err = bmp180_i2c_write_u8(BMP180_CONTROL_REG, BMP180_TEMP_CMD);
    if (err != ESP_OK)
    {
        return err;
    }

    mmosal_task_sleep(5);

    err = bmp180_i2c_read(BMP180_DATA_REG, data, sizeof(data));
    if (err != ESP_OK)
    {
        return err;
    }

    *ut = (int32_t)bmp180_read_u16_be(data);
    return ESP_OK;
}

static esp_err_t bmp180_read_raw_pressure(int32_t *up)
{
    uint8_t data[3] = {0};
    esp_err_t err;

    if (up == NULL)
    {
        return ESP_ERR_INVALID_ARG;
    }

    err = bmp180_i2c_write_u8(BMP180_CONTROL_REG,
                              (uint8_t)(BMP180_PRESSURE_CMD + (BMP180_OSS << 6)));
    if (err != ESP_OK)
    {
        return err;
    }

    mmosal_task_sleep(5);

    err = bmp180_i2c_read(BMP180_DATA_REG, data, sizeof(data));
    if (err != ESP_OK)
    {
        return err;
    }

    *up = (int32_t)((((uint32_t)data[0] << 16) |
                    ((uint32_t)data[1] << 8) |
                    data[2]) >> (8 - BMP180_OSS));
    return ESP_OK;
}

static esp_err_t bmp180_read_measurement(const struct bmp180_calibration *cal,
                                         int32_t *temperature_deci_c,
                                         int32_t *pressure_pa)
{
    int32_t ut;
    int32_t up;
    int32_t x1;
    int32_t x2;
    int32_t x3;
    int32_t b3;
    int32_t b5;
    int32_t b6;
    uint32_t b4;
    uint32_t b7;
    int32_t p;
    esp_err_t err;

    if ((cal == NULL) || (temperature_deci_c == NULL) || (pressure_pa == NULL))
    {
        return ESP_ERR_INVALID_ARG;
    }

    err = bmp180_read_raw_temperature(&ut);
    if (err != ESP_OK)
    {
        return err;
    }

    err = bmp180_read_raw_pressure(&up);
    if (err != ESP_OK)
    {
        return err;
    }

    x1 = ((ut - (int32_t)cal->ac6) * (int32_t)cal->ac5) >> 15;
    x2 = ((int32_t)cal->mc << 11) / (x1 + (int32_t)cal->md);
    b5 = x1 + x2;
    *temperature_deci_c = (b5 + 8) >> 4;

    b6 = b5 - 4000;
    x1 = ((int32_t)cal->b2 * ((b6 * b6) >> 12)) >> 11;
    x2 = ((int32_t)cal->ac2 * b6) >> 11;
    x3 = x1 + x2;
    b3 = (((((int32_t)cal->ac1 * 4) + x3) << BMP180_OSS) + 2) >> 2;

    x1 = ((int32_t)cal->ac3 * b6) >> 13;
    x2 = ((int32_t)cal->b1 * ((b6 * b6) >> 12)) >> 16;
    x3 = ((x1 + x2) + 2) >> 2;
    b4 = ((uint32_t)cal->ac4 * (uint32_t)(x3 + 32768)) >> 15;
    b7 = ((uint32_t)up - (uint32_t)b3) * (uint32_t)(50000 >> BMP180_OSS);

    if (b4 == 0)
    {
        return ESP_FAIL;
    }

    if (b7 < 0x80000000UL)
    {
        p = (int32_t)((b7 * 2) / b4);
    }
    else
    {
        p = (int32_t)((b7 / b4) * 2);
    }

    x1 = (p >> 8) * (p >> 8);
    x1 = (x1 * 3038) >> 16;
    x2 = (-7357 * p) >> 16;
    p += (x1 + x2 + 3791) >> 4;

    *pressure_pa = p;
    return ESP_OK;
}

static bool bmp180_measurement_is_plausible(int32_t temperature_deci_c, int32_t pressure_pa)
{
    return (temperature_deci_c >= BMP180_TEMP_MIN_DECI_C) &&
           (temperature_deci_c <= BMP180_TEMP_MAX_DECI_C) &&
           (pressure_pa >= BMP180_PRESSURE_MIN_PA) &&
           (pressure_pa <= BMP180_PRESSURE_MAX_PA);
}

static esp_err_t bmp180_read_filtered_measurement(const struct bmp180_calibration *cal,
                                                  int32_t *temperature_deci_c,
                                                  int32_t *pressure_pa)
{
    int32_t temperature_sum = 0;
    int32_t pressure_sum = 0;
    uint32_t valid_samples = 0;
    esp_err_t last_err = ESP_FAIL;
    unsigned ii;

    if ((cal == NULL) || (temperature_deci_c == NULL) || (pressure_pa == NULL))
    {
        return ESP_ERR_INVALID_ARG;
    }

    for (ii = 0; ii < BMP180_SAMPLE_RETRIES; ii++)
    {
        int32_t temp_sample = 0;
        int32_t pressure_sample = 0;
        esp_err_t err = bmp180_read_measurement(cal, &temp_sample, &pressure_sample);

        if (err != ESP_OK)
        {
            last_err = err;
            continue;
        }

        if (!bmp180_measurement_is_plausible(temp_sample, pressure_sample))
        {
            last_err = ESP_ERR_INVALID_RESPONSE;
            continue;
        }

        temperature_sum += temp_sample;
        pressure_sum += pressure_sample;
        valid_samples++;
        last_err = ESP_OK;
    }

    if (valid_samples == 0)
    {
        return last_err;
    }

    *temperature_deci_c = temperature_sum / (int32_t)valid_samples;
    *pressure_pa = pressure_sum / (int32_t)valid_samples;
    return ESP_OK;
}

static void bmp180_format_temperature(int32_t temperature_deci_c,
                                      char *buffer,
                                      size_t buffer_len)
{
    bool negative = temperature_deci_c < 0;
    int32_t absolute = negative ? -temperature_deci_c : temperature_deci_c;

    (void)snprintf(buffer,
                   buffer_len,
                   "%s%ld.%01ld",
                   negative ? "-" : "",
                   (long)(absolute / 10),
                   (long)(absolute % 10));
}

static void bmp180_set_error(esp_err_t err)
{
    if (bmp180_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(bmp180_state.lock);
    bmp180_state.detected = false;
    bmp180_state.valid = false;
    bmp180_state.last_error = err;
    MMOSAL_MUTEX_RELEASE(bmp180_state.lock);
}

static void bmp180_set_detected(void)
{
    if (bmp180_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(bmp180_state.lock);
    bmp180_state.detected = true;
    bmp180_state.last_error = ESP_OK;
    MMOSAL_MUTEX_RELEASE(bmp180_state.lock);
}

static void bmp180_publish_measurement(int32_t temperature_deci_c, int32_t pressure_pa)
{
    if (bmp180_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(bmp180_state.lock);
    bmp180_state.detected = true;
    bmp180_state.temperature_deci_c = temperature_deci_c;
    bmp180_state.pressure_pa = pressure_pa;
    bmp180_state.last_update_ms = mmosal_get_time_ms();
    bmp180_state.valid = true;
    bmp180_state.last_error = ESP_OK;
    MMOSAL_MUTEX_RELEASE(bmp180_state.lock);
}

static bool bmp180_get_latest(int32_t *temperature_deci_c, int32_t *pressure_pa)
{
    bool valid = false;

    if ((bmp180_state.lock == NULL) || (temperature_deci_c == NULL) || (pressure_pa == NULL))
    {
        return false;
    }

    MMOSAL_MUTEX_GET_INF(bmp180_state.lock);
    if (bmp180_state.valid &&
        ((mmosal_get_time_ms() - bmp180_state.last_update_ms) <= BMP180_DATA_STALE_MS))
    {
        *temperature_deci_c = bmp180_state.temperature_deci_c;
        *pressure_pa = bmp180_state.pressure_pa;
        valid = true;
    }
    MMOSAL_MUTEX_RELEASE(bmp180_state.lock);

    return valid;
}

static void bmp180_get_status(bool *detected,
                              bool *valid,
                              esp_err_t *last_error,
                              int32_t *temperature_deci_c,
                              int32_t *pressure_pa)
{
    if ((bmp180_state.lock == NULL) ||
        (detected == NULL) ||
        (valid == NULL) ||
        (last_error == NULL) ||
        (temperature_deci_c == NULL) ||
        (pressure_pa == NULL))
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(bmp180_state.lock);
    *detected = bmp180_state.detected;
    *valid = bmp180_state.valid &&
             ((mmosal_get_time_ms() - bmp180_state.last_update_ms) <= BMP180_DATA_STALE_MS);
    *last_error = bmp180_state.last_error;
    *temperature_deci_c = bmp180_state.temperature_deci_c;
    *pressure_pa = bmp180_state.pressure_pa;
    MMOSAL_MUTEX_RELEASE(bmp180_state.lock);
}

static void bmp180_get_cot_remarks(char *buffer, size_t buffer_len)
{
    int32_t temperature_deci_c;
    int32_t pressure_pa;
    char temperature_text[16] = {0};

    if ((buffer == NULL) || (buffer_len == 0))
    {
        return;
    }

    buffer[0] = '\0';

    if (!bmp180_get_latest(&temperature_deci_c, &pressure_pa))
    {
        return;
    }

    bmp180_format_temperature(temperature_deci_c,
                              temperature_text,
                              sizeof(temperature_text));
    (void)snprintf(buffer,
                   buffer_len,
                   " temp=%sC pressure=%ldPa",
                   temperature_text,
                   (long)pressure_pa);
}

static void bmp180_task(void *arg)
{
    struct bmp180_calibration cal = {0};
    uint8_t chip_id = 0;
    esp_err_t err;

    (void)arg;

    err = bmp180_init_i2c();
    if (err != ESP_OK)
    {
        bmp180_set_error(err);
        printf("BMP180 I2C init failed, err=%s\n", esp_err_to_name(err));
        mmosal_task_delete(NULL);
        return;
    }

    bmp180_scan_i2c_bus();

    err = bmp180_i2c_read(BMP180_CHIP_ID_REG, &chip_id, sizeof(chip_id));
    if (err != ESP_OK)
    {
        bmp180_set_error(err);
        printf("BMP180 not detected at I2C address 0x%02x, err=%s\n",
               BMP180_I2C_ADDR,
               esp_err_to_name(err));
        printf("Check 3V3, common GND, SDA/SCL swap, and whether the module is really BMP180/BMP085.\n");
        mmosal_task_delete(NULL);
        return;
    }

    if (chip_id != BMP180_CHIP_ID)
    {
        bmp180_set_error(ESP_ERR_NOT_FOUND);
        printf("BMP180 unexpected chip id 0x%02x, expected 0x%02x\n",
               chip_id,
               BMP180_CHIP_ID);
        mmosal_task_delete(NULL);
        return;
    }

    err = bmp180_read_calibration(&cal);
    if (err != ESP_OK)
    {
        bmp180_set_error(err);
        printf("BMP180 calibration read failed, err=%s\n", esp_err_to_name(err));
        mmosal_task_delete(NULL);
        return;
    }

    bmp180_set_detected();

    printf("BMP180 ready on SDA=GPIO%d SCL=GPIO%d address=0x%02x\n",
           (int)BMP180_I2C_SDA_PIN,
           (int)BMP180_I2C_SCL_PIN,
           BMP180_I2C_ADDR);

    while (true)
    {
        int32_t temperature_deci_c = 0;
        int32_t pressure_pa = 0;
        char temperature_text[16] = {0};

        err = bmp180_read_filtered_measurement(&cal, &temperature_deci_c, &pressure_pa);
        if (err == ESP_OK)
        {
            bmp180_publish_measurement(temperature_deci_c, pressure_pa);
            bmp180_format_temperature(temperature_deci_c,
                                      temperature_text,
                                      sizeof(temperature_text));
            printf("BMP180: temp=%s C pressure=%ld Pa\n",
                   temperature_text,
                   (long)pressure_pa);
        }
        else
        {
            bmp180_set_error(err);
            printf("BMP180 read failed or unstable, err=%s\n", esp_err_to_name(err));
        }

        mmosal_task_sleep(BMP180_SAMPLE_INTERVAL_MS);
    }
}

static void start_bmp180(void)
{
    if (bmp180_state.task != NULL)
    {
        return;
    }

    bmp180_state.lock = mmosal_mutex_create("bmp180");
    if (bmp180_state.lock == NULL)
    {
        printf("Failed to create BMP180 mutex.\n");
        return;
    }

    bmp180_state.task = mmosal_task_create(bmp180_task, NULL, MMOSAL_TASK_PRI_LOW,
                                           BMP180_TASK_STACK_WORDS, "bmp180");
    if (bmp180_state.task == NULL)
    {
        printf("Failed to create BMP180 task.\n");
    }
}
#endif

#if ATAK_COT_ENABLE
/** Background task that periodically unicasts CoT to ATAK. */
static struct mmosal_task *atak_cot_task_handle = NULL;

#if ATAK_GPS_ENABLE
/** Shared GPS fix state populated from NMEA sentences. */
static struct
{
    struct mmosal_mutex *lock;
    struct mmosal_task *task;
    bool valid;
    bool first_fix_reported;
    bool nmea_seen;
    char lat[20];
    char lon[20];
    char hae[16];
    uint8_t satellites;
    uint32_t last_update_ms;
    uint32_t last_sentence_ms;
    uint32_t sentence_count;
    esp_err_t last_error;
} atak_gps_state = {0};
#endif

static bool atak_cot_get_position_strings(char *lat, size_t lat_len,
                                          char *lon, size_t lon_len,
                                          char *hae, size_t hae_len,
                                          bool *using_gps, uint8_t *satellites);

static bool atak_cot_is_time_valid(void)
{
    return time(NULL) >= (time_t)ATAK_COT_MIN_VALID_EPOCH;
}

#if ATAK_GPS_ENABLE
static bool atak_nmea_coord_to_decimal_string(const char *nmea_value, char hemisphere,
                                              bool is_latitude, char *out, size_t out_len)
{
    const int degree_digits = is_latitude ? 2 : 3;
    uint32_t degrees = 0;
    uint64_t minutes_value = 0;
    uint64_t minutes_scale = 1;
    uint64_t scaled_degrees;
    bool seen_decimal = false;
    bool negative;
    const char *minutes_ptr;
    int ii;

    if ((nmea_value == NULL) || (out == NULL) || (out_len == 0))
    {
        return false;
    }

    if ((int)strlen(nmea_value) <= degree_digits)
    {
        return false;
    }

    for (ii = 0; ii < degree_digits; ii++)
    {
        if (!isdigit((unsigned char)nmea_value[ii]))
        {
            return false;
        }
        degrees = (degrees * 10U) + (uint32_t)(nmea_value[ii] - '0');
    }

    minutes_ptr = nmea_value + degree_digits;
    while (*minutes_ptr != '\0')
    {
        if (*minutes_ptr == '.')
        {
            if (seen_decimal)
            {
                return false;
            }
            seen_decimal = true;
        }
        else if (isdigit((unsigned char)*minutes_ptr))
        {
            minutes_value = (minutes_value * 10ULL) + (uint64_t)(*minutes_ptr - '0');
            if (seen_decimal)
            {
                minutes_scale *= 10ULL;
            }
        }
        else
        {
            return false;
        }

        minutes_ptr++;
    }

    scaled_degrees = (uint64_t)degrees * 1000000ULL;
    scaled_degrees += (minutes_value * 1000000ULL) / (60ULL * minutes_scale);
    negative = (hemisphere == 'S') || (hemisphere == 'W');

    snprintf(out, out_len, "%s%llu.%06llu",
             negative ? "-" : "",
             (unsigned long long)(scaled_degrees / 1000000ULL),
             (unsigned long long)(scaled_degrees % 1000000ULL));
    return true;
}

static unsigned atak_nmea_split_fields(char *sentence, char **fields, unsigned max_fields)
{
    char *cursor = sentence;
    char *checksum = strchr(sentence, '*');
    unsigned count = 0;

    if ((sentence == NULL) || (fields == NULL) || (max_fields == 0))
    {
        return 0;
    }

    if (checksum != NULL)
    {
        *checksum = '\0';
    }

    while ((cursor != NULL) && (count < max_fields))
    {
        char *comma = strchr(cursor, ',');
        fields[count++] = cursor;
        if (comma == NULL)
        {
            break;
        }

        *comma = '\0';
        cursor = comma + 1;
    }

    return count;
}

static void atak_gps_set_error(esp_err_t err)
{
    if (atak_gps_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(atak_gps_state.lock);
    atak_gps_state.last_error = err;
    MMOSAL_MUTEX_RELEASE(atak_gps_state.lock);
}

static void atak_gps_note_sentence(void)
{
    if (atak_gps_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(atak_gps_state.lock);
    atak_gps_state.nmea_seen = true;
    atak_gps_state.last_sentence_ms = mmosal_get_time_ms();
    atak_gps_state.sentence_count++;
    atak_gps_state.last_error = ESP_OK;
    MMOSAL_MUTEX_RELEASE(atak_gps_state.lock);
}

static void atak_gps_publish_fix(const char *lat, const char *lon, const char *hae,
                                 uint8_t satellites)
{
    if (atak_gps_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(atak_gps_state.lock);
    (void)mmosal_safer_strcpy(atak_gps_state.lat, lat, sizeof(atak_gps_state.lat));
    (void)mmosal_safer_strcpy(atak_gps_state.lon, lon, sizeof(atak_gps_state.lon));
    (void)mmosal_safer_strcpy(atak_gps_state.hae, hae, sizeof(atak_gps_state.hae));
    atak_gps_state.satellites = satellites;
    atak_gps_state.valid = true;
    atak_gps_state.last_update_ms = mmosal_get_time_ms();
    atak_gps_state.last_error = ESP_OK;
    MMOSAL_MUTEX_RELEASE(atak_gps_state.lock);

    if (!atak_gps_state.first_fix_reported)
    {
        printf("GPS fix acquired: lat=%s lon=%s sats=%u\n", lat, lon, (unsigned)satellites);
        atak_gps_state.first_fix_reported = true;
    }
}

static void atak_gps_process_gga(char *sentence)
{
    char *fields[16] = {0};
    char lat[20] = {0};
    char lon[20] = {0};
    unsigned field_count = atak_nmea_split_fields(sentence, fields, 16);
    uint8_t satellites = 0;

    if (field_count < 10)
    {
        return;
    }

    if ((fields[6][0] == '\0') || (fields[6][0] == '0'))
    {
        return;
    }

    if (!atak_nmea_coord_to_decimal_string(fields[2], fields[3][0], true, lat, sizeof(lat)) ||
        !atak_nmea_coord_to_decimal_string(fields[4], fields[5][0], false, lon, sizeof(lon)))
    {
        return;
    }

    satellites = (uint8_t)atoi(fields[7]);
    atak_gps_publish_fix(lat, lon,
                         (fields[9][0] != '\0') ? fields[9] : ATAK_COT_HAE,
                         satellites);
}

static void atak_gps_process_rmc(char *sentence)
{
    char *fields[16] = {0};
    char lat[20] = {0};
    char lon[20] = {0};
    char hae[16] = ATAK_COT_HAE;
    uint8_t satellites = 0;
    unsigned field_count = atak_nmea_split_fields(sentence, fields, 16);

    if (field_count < 7)
    {
        return;
    }

    if (fields[2][0] != 'A')
    {
        return;
    }

    if (!atak_nmea_coord_to_decimal_string(fields[3], fields[4][0], true, lat, sizeof(lat)) ||
        !atak_nmea_coord_to_decimal_string(fields[5], fields[6][0], false, lon, sizeof(lon)))
    {
        return;
    }

    if (atak_gps_state.lock != NULL)
    {
        MMOSAL_MUTEX_GET_INF(atak_gps_state.lock);
        if (atak_gps_state.hae[0] != '\0')
        {
            (void)mmosal_safer_strcpy(hae, atak_gps_state.hae, sizeof(hae));
        }
        satellites = atak_gps_state.satellites;
        MMOSAL_MUTEX_RELEASE(atak_gps_state.lock);
    }

    atak_gps_publish_fix(lat, lon, hae, satellites);
}

static void atak_gps_process_sentence(char *sentence)
{
    if ((sentence == NULL) || (sentence[0] != '$'))
    {
        return;
    }

    atak_gps_note_sentence();

    if ((strncmp(sentence, "$GPGGA", 6) == 0) || (strncmp(sentence, "$GNGGA", 6) == 0))
    {
        atak_gps_process_gga(sentence);
    }
    else if ((strncmp(sentence, "$GPRMC", 6) == 0) || (strncmp(sentence, "$GNRMC", 6) == 0))
    {
        atak_gps_process_rmc(sentence);
    }
}

static bool atak_gps_has_recent_fix(char *lat, size_t lat_len,
                                    char *lon, size_t lon_len,
                                    char *hae, size_t hae_len,
                                    uint8_t *satellites)
{
    bool valid = false;

    if (atak_gps_state.lock == NULL)
    {
        return false;
    }

    MMOSAL_MUTEX_GET_INF(atak_gps_state.lock);
    if (atak_gps_state.valid &&
        ((mmosal_get_time_ms() - atak_gps_state.last_update_ms) <= ATAK_GPS_FIX_STALE_MS))
    {
        (void)mmosal_safer_strcpy(lat, atak_gps_state.lat, lat_len);
        (void)mmosal_safer_strcpy(lon, atak_gps_state.lon, lon_len);
        (void)mmosal_safer_strcpy(hae, atak_gps_state.hae, hae_len);
        *satellites = atak_gps_state.satellites;
        valid = true;
    }
    MMOSAL_MUTEX_RELEASE(atak_gps_state.lock);

    return valid;
}

struct atak_gps_status
{
    bool nmea_seen;
    bool fix_valid;
    char lat[20];
    char lon[20];
    char hae[16];
    uint8_t satellites;
    uint32_t fix_age_ms;
    uint32_t nmea_age_ms;
    uint32_t sentence_count;
    esp_err_t last_error;
};

static void atak_gps_get_status(struct atak_gps_status *status)
{
    uint32_t now_ms = mmosal_get_time_ms();

    if (status == NULL)
    {
        return;
    }

    memset(status, 0, sizeof(*status));
    status->last_error = ESP_OK;

    if (atak_gps_state.lock == NULL)
    {
        return;
    }

    MMOSAL_MUTEX_GET_INF(atak_gps_state.lock);
    status->nmea_seen = atak_gps_state.nmea_seen;
    status->sentence_count = atak_gps_state.sentence_count;
    status->last_error = atak_gps_state.last_error;

    if (atak_gps_state.nmea_seen)
    {
        status->nmea_age_ms = now_ms - atak_gps_state.last_sentence_ms;
    }

    if (atak_gps_state.valid)
    {
        status->fix_age_ms = now_ms - atak_gps_state.last_update_ms;
        status->fix_valid = status->fix_age_ms <= ATAK_GPS_FIX_STALE_MS;
        (void)mmosal_safer_strcpy(status->lat, atak_gps_state.lat, sizeof(status->lat));
        (void)mmosal_safer_strcpy(status->lon, atak_gps_state.lon, sizeof(status->lon));
        (void)mmosal_safer_strcpy(status->hae, atak_gps_state.hae, sizeof(status->hae));
        status->satellites = atak_gps_state.satellites;
    }

    MMOSAL_MUTEX_RELEASE(atak_gps_state.lock);
}

static void atak_gps_task(void *arg)
{
    uart_config_t uart_cfg = {
        .baud_rate = ATAK_GPS_UART_BAUD_RATE,
        .data_bits = UART_DATA_8_BITS,
        .parity = UART_PARITY_DISABLE,
        .stop_bits = UART_STOP_BITS_1,
        .flow_ctrl = UART_HW_FLOWCTRL_DISABLE,
        .source_clk = UART_SCLK_DEFAULT,
    };
    esp_err_t err;
    char line[ATAK_GPS_LINE_MAX_LEN] = {0};
    size_t line_len = 0;
    uint8_t byte = 0;

    (void)arg;

    err = uart_driver_install(ATAK_GPS_UART_NUM, ATAK_GPS_UART_BUF_SIZE, 0, 0, NULL, 0);
    if (err != ESP_OK)
    {
        atak_gps_set_error(err);
        printf("GPS UART driver install failed, err=%d\n", (int)err);
        mmosal_task_delete(NULL);
        return;
    }

    err = uart_param_config(ATAK_GPS_UART_NUM, &uart_cfg);
    if (err != ESP_OK)
    {
        atak_gps_set_error(err);
        printf("GPS UART config failed, err=%d\n", (int)err);
        mmosal_task_delete(NULL);
        return;
    }

    err = uart_set_pin(ATAK_GPS_UART_NUM, ATAK_GPS_UART_TX_PIN, ATAK_GPS_UART_RX_PIN,
                       UART_PIN_NO_CHANGE, UART_PIN_NO_CHANGE);
    if (err != ESP_OK)
    {
        atak_gps_set_error(err);
        printf("GPS UART pin config failed, err=%d\n", (int)err);
        mmosal_task_delete(NULL);
        return;
    }

    printf("GPS UART listening on UART%d RX=GPIO%d TX=GPIO%d baud=%d\n",
           (int)ATAK_GPS_UART_NUM,
           (int)ATAK_GPS_UART_RX_PIN,
           (int)ATAK_GPS_UART_TX_PIN,
           (int)ATAK_GPS_UART_BAUD_RATE);

    while (true)
    {
        int ret = uart_read_bytes(ATAK_GPS_UART_NUM, &byte, sizeof(byte), pdMS_TO_TICKS(1000));

        if (ret <= 0)
        {
            continue;
        }

        if (byte == '$')
        {
            line_len = 0;
            line[line_len++] = (char)byte;
            continue;
        }

        if (line_len == 0)
        {
            continue;
        }

        if (byte == '\r')
        {
            continue;
        }

        if (byte == '\n')
        {
            line[line_len] = '\0';
            atak_gps_process_sentence(line);
            line_len = 0;
            continue;
        }

        if (line_len < (sizeof(line) - 1))
        {
            line[line_len++] = (char)byte;
        }
        else
        {
            line_len = 0;
        }
    }
}

static void start_atak_gps(void)
{
    if (atak_gps_state.task != NULL)
    {
        return;
    }

    atak_gps_state.lock = mmosal_mutex_create("atak_gps");
    if (atak_gps_state.lock == NULL)
    {
        printf("Failed to create GPS mutex.\n");
        return;
    }

    atak_gps_state.task = mmosal_task_create(atak_gps_task, NULL, MMOSAL_TASK_PRI_LOW,
                                             ATAK_GPS_TASK_STACK_WORDS, "atak_gps");
    if (atak_gps_state.task == NULL)
    {
        printf("Failed to create GPS task.\n");
    }
}
#endif

static unsigned atak_cot_month_to_int(const char *month)
{
    static const char *const months[] = {
        "Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
    };
    unsigned ii;

    for (ii = 0; ii < (sizeof(months) / sizeof(months[0])); ii++)
    {
        if (strncmp(month, months[ii], 3) == 0)
        {
            return ii + 1;
        }
    }

    return 1;
}

static int64_t atak_cot_days_from_civil(int year, unsigned month, unsigned day)
{
    int shifted_month;

    year -= month <= 2;
    shifted_month = (int)month + ((month > 2) ? -3 : 9);
    const int era = (year >= 0 ? year : year - 399) / 400;
    const unsigned yoe = (unsigned)(year - era * 400);
    const unsigned doy = (153U * (unsigned)shifted_month + 2U) / 5U + day - 1U;
    const unsigned doe = yoe * 365 + yoe / 4 - yoe / 100 + doy;

    return ((int64_t)era * 146097) + (int64_t)doe - 719468;
}

static time_t atak_cot_get_build_epoch(void)
{
    unsigned month = atak_cot_month_to_int(__DATE__);
    unsigned day = 0;
    unsigned year = 0;
    unsigned hour = 0;
    unsigned minute = 0;
    unsigned second = 0;
    int64_t days;

    (void)sscanf(__DATE__ + 4, "%u %u", &day, &year);
    (void)sscanf(__TIME__, "%u:%u:%u", &hour, &minute, &second);

    days = atak_cot_days_from_civil((int)year, month, day);

    return (time_t)(days * 86400 + (int64_t)hour * 3600 +
                    (int64_t)minute * 60 + (int64_t)second);
}

static bool atak_cot_apply_build_time_fallback(void)
{
    time_t build_epoch = atak_cot_get_build_epoch();
    struct timeval tv = {0};

    if (build_epoch < (time_t)ATAK_COT_MIN_VALID_EPOCH)
    {
        return false;
    }

    tv.tv_sec = build_epoch;
    tv.tv_usec = 0;

    if (settimeofday(&tv, NULL) != 0)
    {
        printf("Failed to apply build-time clock fallback, errno=%d\n", errno);
        return false;
    }

    printf("Applied build-time UTC fallback for CoT timestamps.\n");
    return true;
}

static bool atak_cot_format_timestamp(time_t epoch, char *buffer, size_t buffer_len)
{
    struct tm utc_tm = {0};

    if ((buffer == NULL) || (buffer_len == 0))
    {
        return false;
    }

    if (gmtime_r(&epoch, &utc_tm) == NULL)
    {
        return false;
    }

    return strftime(buffer, buffer_len, "%Y-%m-%dT%H:%M:%SZ", &utc_tm) != 0;
}

static bool atak_cot_sync_time(void)
{
    if (atak_cot_is_time_valid())
    {
        return true;
    }

#if ATAK_COT_SNTP_ENABLE
    uint32_t waited_ms = 0;

    printf("Syncing UTC time with SNTP server %s...\n", ATAK_COT_SNTP_SERVER);

    if (!esp_sntp_enabled())
    {
        esp_sntp_setoperatingmode(SNTP_OPMODE_POLL);
        esp_sntp_setservername(0, ATAK_COT_SNTP_SERVER);
        esp_sntp_init();
    }

    while (!atak_cot_is_time_valid() && (waited_ms < ATAK_COT_SNTP_TIMEOUT_MS))
    {
        mmosal_task_sleep(250);
        waited_ms += 250;
    }

    if (atak_cot_is_time_valid())
    {
        printf("UTC time synchronized successfully for CoT.\n");
        return true;
    }

    printf("SNTP sync timed out after %lu ms.\n", (unsigned long)ATAK_COT_SNTP_TIMEOUT_MS);
#endif

    return atak_cot_apply_build_time_fallback() && atak_cot_is_time_valid();
}

static int atak_cot_build_payload(char *buffer, size_t buffer_len,
                                  const char *now_iso, const char *stale_iso)
{
    struct mmipal_ip_config ip_config = MMIPAL_IP_CONFIG_DEFAULT;
    const char *ip_addr = "0.0.0.0";
    int32_t rssi_dbm = mmwlan_get_rssi();
    char lat[20] = ATAK_COT_LAT;
    char lon[20] = ATAK_COT_LON;
    char hae[16] = ATAK_COT_HAE;
    bool using_gps = false;
    uint8_t satellites = 0;
    char sensor_remarks[64] = {0};

    if (mmipal_get_ip_config(&ip_config) == MMIPAL_SUCCESS)
    {
        if (ip_config.ip_addr[0] != '\0')
        {
            ip_addr = ip_config.ip_addr;
        }
    }

    if (!atak_cot_get_position_strings(lat, sizeof(lat), lon, sizeof(lon), hae, sizeof(hae),
                                       &using_gps, &satellites))
    {
        return -2;
    }

#if BMP180_ENABLE
    bmp180_get_cot_remarks(sensor_remarks, sizeof(sensor_remarks));
#endif

    return snprintf(
        buffer,
        buffer_len,
        "<event version=\"2.0\" uid=\"" ATAK_COT_UID "\" type=\"" ATAK_COT_TYPE
        "\" how=\"" ATAK_COT_HOW "\" time=\"%s\" start=\"%s\" stale=\"%s\">"
        "<detail><contact callsign=\"" ATAK_COT_CALLSIGN "\"/>"
        "<remarks>ip=%s rssi=%lddBm pos=%s sats=%u%s</remarks></detail>"
        "<point lat=\"%s\" lon=\"%s\" hae=\"%s"
        "\" ce=\"" ATAK_COT_CE "\" le=\"" ATAK_COT_LE "\"/>"
        "</event>",
        now_iso,
        now_iso,
        stale_iso,
        ip_addr,
        (long)rssi_dbm,
        using_gps ? "gps" : "fixed",
        (unsigned)satellites,
        sensor_remarks,
        lat,
        lon,
        hae);
}

static void atak_cot_task(void *arg)
{
    int sock = -1;
    struct sockaddr_in target_addr = {0};
    uint32_t last_no_fix_log_ms = 0;

    (void)arg;

    if (inet_pton(AF_INET, ATAK_COT_IP, &target_addr.sin_addr) != 1)
    {
        printf("Invalid ATAK target IPv4 address: %s\n", ATAK_COT_IP);
        mmosal_task_delete(NULL);
        return;
    }

    target_addr.sin_family = AF_INET;
    target_addr.sin_port = htons(ATAK_COT_PORT);

    printf("ATAK CoT unicast enabled for %s:%u (%s / %s)\n",
           ATAK_COT_IP,
           (unsigned)ATAK_COT_PORT,
           ATAK_COT_UID,
           ATAK_COT_CALLSIGN);

    (void)atak_cot_sync_time();

    while (true)
    {
        char now_iso[21] = {0};
        char stale_iso[21] = {0};
        char payload[ATAK_COT_PAYLOAD_MAX_LEN] = {0};
        time_t now_epoch;
        time_t stale_epoch;
        int payload_len;
        int sent_bytes;

        if (mmipal_get_link_state() != MMIPAL_LINK_UP)
        {
            mmosal_task_sleep(1000);
            continue;
        }

        if (!atak_cot_is_time_valid() && !atak_cot_sync_time())
        {
            printf("UTC clock is still invalid; retrying CoT send later.\n");
            mmosal_task_sleep(2000);
            continue;
        }

        if (sock < 0)
        {
            sock = socket(AF_INET, SOCK_DGRAM, IPPROTO_UDP);
            if (sock < 0)
            {
                printf("Failed to create ATAK UDP socket, errno=%d\n", errno);
                mmosal_task_sleep(2000);
                continue;
            }
        }

        now_epoch = time(NULL);
        stale_epoch = now_epoch + ATAK_COT_STALE_SEC;

        if (!atak_cot_format_timestamp(now_epoch, now_iso, sizeof(now_iso)) ||
            !atak_cot_format_timestamp(stale_epoch, stale_iso, sizeof(stale_iso)))
        {
            printf("Failed to format CoT timestamps.\n");
            mmosal_task_sleep(2000);
            continue;
        }

        payload_len = atak_cot_build_payload(payload, sizeof(payload), now_iso, stale_iso);
        if (payload_len == -2)
        {
            uint32_t now_ms = mmosal_get_time_ms();

            if ((last_no_fix_log_ms == 0U) ||
                ((now_ms - last_no_fix_log_ms) >= ATAK_COT_NO_FIX_LOG_INTERVAL_MS))
            {
                printf("GPS fix belum valid; marker slave ke ATAK belum dikirim.\n");
                last_no_fix_log_ms = now_ms;
            }

            mmosal_task_sleep(1000);
            continue;
        }

        if ((payload_len < 0) || ((size_t)payload_len >= sizeof(payload)))
        {
            printf("ATAK CoT payload exceeded %u bytes.\n", (unsigned)sizeof(payload));
            mmosal_task_sleep(2000);
            continue;
        }

        sent_bytes = (int)sendto(sock, payload, (size_t)payload_len, 0,
                                 (const struct sockaddr *)&target_addr,
                                 sizeof(target_addr));
        if (sent_bytes < 0)
        {
            printf("Failed sending CoT to ATAK, errno=%d. Recreating socket...\n", errno);
            close(sock);
            sock = -1;
            mmosal_task_sleep(2000);
            continue;
        }

        printf("CoT slave terkirim ke ATAK %s:%u (%s)\n",
               ATAK_COT_IP,
               (unsigned)ATAK_COT_PORT,
               now_iso);
        mmosal_task_sleep(ATAK_COT_INTERVAL_MS);
    }
}

static bool atak_cot_get_position_strings(char *lat, size_t lat_len,
                                          char *lon, size_t lon_len,
                                          char *hae, size_t hae_len,
                                          bool *using_gps, uint8_t *satellites)
{
    (void)mmosal_safer_strcpy(lat, ATAK_COT_LAT, lat_len);
    (void)mmosal_safer_strcpy(lon, ATAK_COT_LON, lon_len);
    (void)mmosal_safer_strcpy(hae, ATAK_COT_HAE, hae_len);
    *using_gps = false;
    *satellites = 0;

#if ATAK_GPS_ENABLE
    if (atak_gps_has_recent_fix(lat, lat_len, lon, lon_len, hae, hae_len, satellites))
    {
        *using_gps = true;
        return true;
    }
#endif

#if ATAK_COT_REQUIRE_GPS_FIX
    return false;
#else
    return true;
#endif
}

static void start_atak_cot(void)
{
    if (atak_cot_task_handle != NULL)
    {
        return;
    }

    atak_cot_task_handle = mmosal_task_create(atak_cot_task, NULL, MMOSAL_TASK_PRI_LOW,
                                              ATAK_COT_TASK_STACK_WORDS, "atak_cot");
    if (atak_cot_task_handle == NULL)
    {
        printf("Failed to create ATAK CoT task.\n");
    }
}
#endif

#if STATUS_WEB_ENABLE
static const char status_web_index_html[] =
    "<!doctype html><html><head><meta charset='utf-8'>"
    "<meta name='viewport' content='width=device-width,initial-scale=1'>"
    "<title>ESP32 HaLow Status</title>"
    "<style>"
    "body{font-family:Arial,sans-serif;margin:24px;background:#0f172a;color:#e2e8f0;}"
    "h1{margin:0 0 12px;font-size:24px;}p{color:#94a3b8;}pre{background:#111827;padding:16px;"
    "border-radius:8px;overflow:auto;}code{font-family:Consolas,monospace;}"
    "</style></head><body><h1>ESP32 HaLow Status</h1>"
    "<p id='summary'>Loading...</p><pre id='payload'></pre>"
    "<script>"
    "async function refresh(){"
    "const controller=new AbortController();"
    "const timer=setTimeout(()=>controller.abort(),8000);"
    "try{"
    "const res=await fetch('/api/status',{cache:'no-store',signal:controller.signal});"
    "const data=await res.json();"
    "document.getElementById('summary').textContent="
    "'IP '+data.ipv4+' | link '+data.link_state+' | RSSI '+data.rssi_dbm+' dBm | GPS '+data.gps.state+' | sats '+data.gps.satellites;"
    "document.getElementById('payload').textContent=JSON.stringify(data,null,2);"
    "}catch(err){"
    "document.getElementById('summary').textContent='Failed to fetch status';"
    "document.getElementById('payload').textContent=String(err);"
    "}finally{"
    "clearTimeout(timer);"
    "}"
    "}"
    "refresh();setInterval(refresh,10000);"
    "</script></body></html>";

static const char *iperf_mode_to_string(enum iperf_type mode)
{
    switch (mode)
    {
    case IPERF_TCP_SERVER:
        return "tcp_server";

    case IPERF_UDP_SERVER:
        return "udp_server";

    case IPERF_TCP_CLIENT:
        return "tcp_client";

    case IPERF_UDP_CLIENT:
        return "udp_client";
    }

    return "unknown";
}

static const char *web_link_state_to_string(enum mmipal_link_state link_state)
{
    switch (link_state)
    {
    case MMIPAL_LINK_UP:
        return "up";

    case MMIPAL_LINK_DOWN:
        return "down";
    }

    return "unknown";
}

static const char *gps_state_to_string(bool nmea_seen, bool fix_valid, esp_err_t last_error)
{
    if (last_error != ESP_OK)
    {
        return "error";
    }

    if (fix_valid)
    {
        return "fix";
    }

    if (nmea_seen)
    {
        return "no_fix";
    }

    return "waiting_nmea";
}

static int build_status_json(char *buffer, size_t buffer_len)
{
    struct mmipal_ip_config ip_config = MMIPAL_IP_CONFIG_DEFAULT;
    const char *ip_addr = "0.0.0.0";
    char atak_target[32] = "";
    int32_t rssi_dbm = mmwlan_get_rssi();
    const char *link_state = web_link_state_to_string(mmipal_get_link_state());
#if IPERF_AUTOSTART
    const char *iperf_mode = iperf_mode_to_string(IPERF_TYPE);
#else
    const char *iperf_mode = "disabled";
#endif
    const char *gps_state = "disabled";
    bool gps_nmea_seen = false;
    bool gps_fix_valid = false;
    char gps_lat[20] = "";
    char gps_lon[20] = "";
    char gps_hae[16] = "";
    uint8_t gps_satellites = 0;
    uint32_t gps_fix_age_ms = 0;
    uint32_t gps_nmea_age_ms = 0;
    uint32_t gps_sentence_count = 0;
    esp_err_t gps_last_error = ESP_OK;

    if ((buffer == NULL) || (buffer_len == 0))
    {
        return -1;
    }

    if (mmipal_get_ip_config(&ip_config) == MMIPAL_SUCCESS)
    {
        if (ip_config.ip_addr[0] != '\0')
        {
            ip_addr = ip_config.ip_addr;
        }
    }

#if ATAK_COT_ENABLE
    (void)snprintf(atak_target, sizeof(atak_target), "%s:%u",
                   ATAK_COT_IP, (unsigned)ATAK_COT_PORT);
#endif

#if ATAK_COT_ENABLE && ATAK_GPS_ENABLE
    {
        struct atak_gps_status status = {0};

        atak_gps_get_status(&status);
        gps_nmea_seen = status.nmea_seen;
        gps_fix_valid = status.fix_valid;
        (void)mmosal_safer_strcpy(gps_lat, status.lat, sizeof(gps_lat));
        (void)mmosal_safer_strcpy(gps_lon, status.lon, sizeof(gps_lon));
        (void)mmosal_safer_strcpy(gps_hae, status.hae, sizeof(gps_hae));
        gps_satellites = status.satellites;
        gps_fix_age_ms = status.fix_age_ms;
        gps_nmea_age_ms = status.nmea_age_ms;
        gps_sentence_count = status.sentence_count;
        gps_last_error = status.last_error;
        gps_state = gps_state_to_string(gps_nmea_seen, gps_fix_valid, gps_last_error);
    }
#endif

    return snprintf(
        buffer,
        buffer_len,
        "{"
        "\"device\":\"" ATAK_COT_UID "\","
        "\"firmware_version\":\"" STATUS_WEB_FIRMWARE_VERSION "\","
        "\"callsign\":\"" ATAK_COT_CALLSIGN "\","
        "\"ipv4\":\"%s\","
        "\"link_state\":\"%s\","
        "\"rssi_dbm\":%ld,"
        "\"uptime_ms\":%lu,"
        "\"iperf_mode\":\"%s\","
        "\"iperf_port\":%u,"
        "\"cot_enabled\":%s,"
        "\"atak_target\":\"%s\","
        "\"gps\":{"
        "\"enabled\":%s,"
        "\"state\":\"%s\","
        "\"uart\":%d,"
        "\"baud\":%d,"
        "\"rx_gpio\":%d,"
        "\"tx_gpio\":%d,"
        "\"nmea_seen\":%s,"
        "\"fix_valid\":%s,"
        "\"latitude\":\"%s\","
        "\"longitude\":\"%s\","
        "\"hae\":\"%s\","
        "\"satellites\":%u,"
        "\"fix_age_ms\":%lu,"
        "\"nmea_age_ms\":%lu,"
        "\"sentence_count\":%lu,"
        "\"last_error\":\"%s\""
        "}"
        "}",
        ip_addr,
        link_state,
        (long)rssi_dbm,
        (unsigned long)mmosal_get_time_ms(),
        iperf_mode,
        (unsigned)IPERF_SERVER_PORT,
#if ATAK_COT_ENABLE
        "true",
#else
        "false",
#endif
        atak_target,
#if ATAK_COT_ENABLE && ATAK_GPS_ENABLE
        "true",
#else
        "false",
#endif
        gps_state,
        (int)ATAK_GPS_UART_NUM,
        (int)ATAK_GPS_UART_BAUD_RATE,
        (int)ATAK_GPS_UART_RX_PIN,
        (int)ATAK_GPS_UART_TX_PIN,
        gps_nmea_seen ? "true" : "false",
        gps_fix_valid ? "true" : "false",
        gps_lat,
        gps_lon,
        gps_hae,
        (unsigned)gps_satellites,
        (unsigned long)gps_fix_age_ms,
        (unsigned long)gps_nmea_age_ms,
        (unsigned long)gps_sentence_count,
        esp_err_to_name(gps_last_error)
    );
}

static esp_err_t status_web_root_handler(httpd_req_t *req)
{
    httpd_resp_set_type(req, "text/html; charset=utf-8");
    httpd_resp_set_hdr(req, "Cache-Control", "no-store");
    httpd_resp_set_hdr(req, "Connection", "close");
    return httpd_resp_send(req, status_web_index_html, HTTPD_RESP_USE_STRLEN);
}

static void status_web_set_api_headers(httpd_req_t *req)
{
    httpd_resp_set_hdr(req, "Access-Control-Allow-Origin", "*");
    httpd_resp_set_hdr(req, "Access-Control-Allow-Methods", "GET, POST, OPTIONS");
    httpd_resp_set_hdr(req, "Access-Control-Allow-Headers", "Content-Type, Accept");
    httpd_resp_set_hdr(req, "Access-Control-Allow-Private-Network", "true");
    httpd_resp_set_hdr(req, "Access-Control-Max-Age", "600");
    httpd_resp_set_hdr(req, "Cache-Control", "no-store");
    httpd_resp_set_hdr(req, "Connection", "close");
}

static esp_err_t status_web_options_handler(httpd_req_t *req)
{
    httpd_resp_set_status(req, "204 No Content");
    status_web_set_api_headers(req);
    return httpd_resp_send(req, NULL, 0);
}

static esp_err_t status_web_api_handler(httpd_req_t *req)
{
    char payload[1024] = {0};
    int payload_len = build_status_json(payload, sizeof(payload));

    if ((payload_len < 0) || ((size_t)payload_len >= sizeof(payload)))
    {
        httpd_resp_set_status(req, "500 Internal Server Error");
        return httpd_resp_sendstr(req, "{\"error\":\"status buffer too small\"}");
    }

    httpd_resp_set_type(req, "application/json");
    status_web_set_api_headers(req);
    return httpd_resp_send(req, payload, payload_len);
}

#if TEXT_MESSAGE_ENABLE
static void text_message_trim(char *value)
{
    char *start = value;
    size_t len;

    if (value == NULL)
    {
        return;
    }

    while (*start != '\0' && isspace((unsigned char)*start))
    {
        start++;
    }

    if (start != value)
    {
        memmove(value, start, strlen(start) + 1);
    }

    len = strlen(value);
    while (len > 0 && isspace((unsigned char)value[len - 1]))
    {
        value[--len] = '\0';
    }
}

static void text_message_copy_clean(char *dest, size_t dest_len, const char *source)
{
    size_t out = 0;
    size_t ii;

    if ((dest == NULL) || (dest_len == 0))
    {
        return;
    }

    dest[0] = '\0';

    if (source == NULL)
    {
        return;
    }

    for (ii = 0; source[ii] != '\0' && out < (dest_len - 1); ii++)
    {
        unsigned char ch = (unsigned char)source[ii];

        if ((ch == '\r') || (ch == '\n'))
        {
            dest[out++] = ' ';
        }
        else if ((ch >= 32U && ch != 127U) || (ch >= 128U))
        {
            dest[out++] = (char)ch;
        }
    }

    dest[out] = '\0';
    text_message_trim(dest);
}

static bool text_message_extract_json_string(const char *body, const char *key,
                                             char *out, size_t out_len)
{
    char needle[40] = {0};
    const char *cursor;
    size_t out_pos = 0;

    if ((body == NULL) || (key == NULL) || (out == NULL) || (out_len == 0))
    {
        return false;
    }

    out[0] = '\0';
    (void)snprintf(needle, sizeof(needle), "\"%s\"", key);
    cursor = strstr(body, needle);
    if (cursor == NULL)
    {
        return false;
    }

    cursor += strlen(needle);
    while (*cursor != '\0' && isspace((unsigned char)*cursor))
    {
        cursor++;
    }
    if (*cursor != ':')
    {
        return false;
    }
    cursor++;
    while (*cursor != '\0' && isspace((unsigned char)*cursor))
    {
        cursor++;
    }
    if (*cursor != '"')
    {
        return false;
    }
    cursor++;

    while (*cursor != '\0' && *cursor != '"' && out_pos < (out_len - 1))
    {
        if (*cursor == '\\' && cursor[1] != '\0')
        {
            cursor++;
            switch (*cursor)
            {
            case 'n':
            case 'r':
            case 't':
                out[out_pos++] = ' ';
                break;

            default:
                out[out_pos++] = *cursor;
                break;
            }
        }
        else
        {
            out[out_pos++] = *cursor;
        }

        cursor++;
    }

    out[out_pos] = '\0';
    text_message_trim(out);
    return true;
}

static void text_message_json_escape(char *dest, size_t dest_len, const char *source)
{
    size_t out = 0;
    size_t ii;

    if ((dest == NULL) || (dest_len == 0))
    {
        return;
    }

    dest[0] = '\0';

    if (source == NULL)
    {
        return;
    }

    for (ii = 0; source[ii] != '\0' && out < (dest_len - 1); ii++)
    {
        char ch = source[ii];

        if ((ch == '"' || ch == '\\') && out < (dest_len - 2))
        {
            dest[out++] = '\\';
            dest[out++] = ch;
        }
        else if (ch == '\n' && out < (dest_len - 3))
        {
            dest[out++] = '\\';
            dest[out++] = 'n';
        }
        else if (ch == '\r' && out < (dest_len - 3))
        {
            dest[out++] = '\\';
            dest[out++] = 'r';
        }
        else if ((unsigned char)ch >= 32U)
        {
            dest[out++] = ch;
        }
    }

    dest[out] = '\0';
}

static int text_message_hex_value(char ch)
{
    if (ch >= '0' && ch <= '9')
    {
        return ch - '0';
    }

    if (ch >= 'a' && ch <= 'f')
    {
        return ch - 'a' + 10;
    }

    if (ch >= 'A' && ch <= 'F')
    {
        return ch - 'A' + 10;
    }

    return -1;
}

static void text_message_url_decode(char *value)
{
    size_t read_pos = 0;
    size_t write_pos = 0;

    if (value == NULL)
    {
        return;
    }

    while (value[read_pos] != '\0')
    {
        if (value[read_pos] == '+')
        {
            value[write_pos++] = ' ';
            read_pos++;
        }
        else if (value[read_pos] == '%' &&
                 value[read_pos + 1] != '\0' &&
                 value[read_pos + 2] != '\0')
        {
            int high = text_message_hex_value(value[read_pos + 1]);
            int low = text_message_hex_value(value[read_pos + 2]);

            if ((high >= 0) && (low >= 0))
            {
                value[write_pos++] = (char)((high << 4) | low);
                read_pos += 3;
            }
            else
            {
                value[write_pos++] = value[read_pos++];
            }
        }
        else
        {
            value[write_pos++] = value[read_pos++];
        }
    }

    value[write_pos] = '\0';
    text_message_trim(value);
}

static bool text_message_url_is_unreserved(char ch)
{
    return ((ch >= 'A' && ch <= 'Z') ||
            (ch >= 'a' && ch <= 'z') ||
            (ch >= '0' && ch <= '9') ||
            ch == '-' || ch == '_' || ch == '.' || ch == '~');
}

static void text_message_url_encode(char *dest, size_t dest_len, const char *source)
{
    static const char hex[] = "0123456789ABCDEF";
    size_t out = 0;
    size_t ii;

    if ((dest == NULL) || (dest_len == 0))
    {
        return;
    }

    dest[0] = '\0';

    if (source == NULL)
    {
        return;
    }

    for (ii = 0; source[ii] != '\0' && out < (dest_len - 1); ii++)
    {
        unsigned char ch = (unsigned char)source[ii];

        if (text_message_url_is_unreserved((char)ch))
        {
            dest[out++] = (char)ch;
        }
        else if (out < (dest_len - 3))
        {
            dest[out++] = '%';
            dest[out++] = hex[(ch >> 4) & 0x0F];
            dest[out++] = hex[ch & 0x0F];
        }
        else
        {
            break;
        }
    }

    dest[out] = '\0';
}

static void text_message_init(void)
{
    if (text_message_state.lock == NULL)
    {
        text_message_state.lock = mmosal_mutex_create("text_msg");
    }
}

static esp_err_t status_web_message_store_and_respond(httpd_req_t *req,
                                                      const char *source_in,
                                                      const char *message_in)
{
    char source[TEXT_MESSAGE_SOURCE_MAX_LEN] = "master-web";
    char message[TEXT_MESSAGE_MAX_LEN + 1] = "";
    char source_json[TEXT_MESSAGE_SOURCE_MAX_LEN * 2] = "";
    char message_json[(TEXT_MESSAGE_MAX_LEN * 2) + 1] = "";
    char payload[TEXT_MESSAGE_RESPONSE_MAX_LEN] = {0};
    uint32_t message_id = 0;
    uint32_t received_ms;
    int payload_len;

    text_message_copy_clean(source, sizeof(source), source_in);
    if (source[0] == '\0')
    {
        (void)mmosal_safer_strcpy(source, "master-web", sizeof(source));
    }

    text_message_copy_clean(message, sizeof(message), message_in);
    if (message[0] == '\0')
    {
        httpd_resp_set_status(req, "400 Bad Request");
        status_web_set_api_headers(req);
        return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"message is empty\"}");
    }

    received_ms = mmosal_get_time_ms();
    text_message_init();
    if (text_message_state.lock != NULL)
    {
        MMOSAL_MUTEX_GET_INF(text_message_state.lock);
        text_message_state.message_count++;
        message_id = text_message_state.message_count;
        text_message_state.last_received_ms = received_ms;
        (void)mmosal_safer_strcpy(text_message_state.last_source,
                                  source,
                                  sizeof(text_message_state.last_source));
        (void)mmosal_safer_strcpy(text_message_state.last_message,
                                  message,
                                  sizeof(text_message_state.last_message));
        MMOSAL_MUTEX_RELEASE(text_message_state.lock);
    }

    printf("Text message #%lu from %s: %s\n",
           (unsigned long)message_id,
           source,
           message);

    text_message_json_escape(source_json, sizeof(source_json), source);
    text_message_json_escape(message_json, sizeof(message_json), message);

    payload_len = snprintf(
        payload,
        sizeof(payload),
        "{"
        "\"ok\":true,"
        "\"device\":\"" ATAK_COT_UID "\","
        "\"message_id\":%lu,"
        "\"received_ms\":%lu,"
        "\"bytes\":%u,"
        "\"source\":\"%s\","
        "\"message\":\"%s\""
        "}",
        (unsigned long)message_id,
        (unsigned long)received_ms,
        (unsigned)strlen(message),
        source_json,
        message_json);

    httpd_resp_set_type(req, "application/json");
    status_web_set_api_headers(req);
    return httpd_resp_send(req, payload, payload_len);
}

static esp_err_t status_web_message_get_handler(httpd_req_t *req)
{
    char source[TEXT_MESSAGE_SOURCE_MAX_LEN] = "";
    char message[TEXT_MESSAGE_MAX_LEN + 1] = "";
    char source_json[TEXT_MESSAGE_SOURCE_MAX_LEN * 2] = "";
    char message_json[(TEXT_MESSAGE_MAX_LEN * 2) + 1] = "";
    char payload[TEXT_MESSAGE_RESPONSE_MAX_LEN] = {0};
    uint32_t message_count = 0;
    uint32_t received_ms = 0;
    int payload_len;
    size_t query_len = httpd_req_get_url_query_len(req);

    if (query_len > 0)
    {
        char query[TEXT_MESSAGE_MAX_LEN + TEXT_MESSAGE_SOURCE_MAX_LEN + 96] = {0};

        if (query_len >= sizeof(query))
        {
            httpd_resp_set_status(req, "414 URI Too Long");
            status_web_set_api_headers(req);
            return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"query string too long\"}");
        }

        if (httpd_req_get_url_query_str(req, query, sizeof(query)) == ESP_OK)
        {
            char query_source[TEXT_MESSAGE_SOURCE_MAX_LEN] = "master-web";
            char query_message[TEXT_MESSAGE_MAX_LEN + 1] = "";

            (void)httpd_query_key_value(query, "source", query_source, sizeof(query_source));
            if (httpd_query_key_value(query, "message", query_message, sizeof(query_message)) != ESP_OK)
            {
                (void)httpd_query_key_value(query, "text", query_message, sizeof(query_message));
            }

            text_message_url_decode(query_source);
            text_message_url_decode(query_message);

            if (query_message[0] != '\0')
            {
                return status_web_message_store_and_respond(req, query_source, query_message);
            }
        }
    }

    if (text_message_state.lock != NULL)
    {
        MMOSAL_MUTEX_GET_INF(text_message_state.lock);
        message_count = text_message_state.message_count;
        received_ms = text_message_state.last_received_ms;
        (void)mmosal_safer_strcpy(source, text_message_state.last_source, sizeof(source));
        (void)mmosal_safer_strcpy(message, text_message_state.last_message, sizeof(message));
        MMOSAL_MUTEX_RELEASE(text_message_state.lock);
    }

    text_message_json_escape(source_json, sizeof(source_json), source);
    text_message_json_escape(message_json, sizeof(message_json), message);

    payload_len = snprintf(
        payload,
        sizeof(payload),
        "{"
        "\"ok\":true,"
        "\"device\":\"" ATAK_COT_UID "\","
        "\"message_count\":%lu,"
        "\"has_message\":%s,"
        "\"last_received_ms\":%lu,"
        "\"last_source\":\"%s\","
        "\"last_message\":\"%s\""
        "}",
        (unsigned long)message_count,
        message_count > 0 ? "true" : "false",
        (unsigned long)received_ms,
        source_json,
        message_json);

    httpd_resp_set_type(req, "application/json");
    status_web_set_api_headers(req);
    return httpd_resp_send(req, payload, payload_len);
}

static esp_err_t status_web_message_post_handler(httpd_req_t *req)
{
    char body[TEXT_MESSAGE_MAX_LEN + TEXT_MESSAGE_SOURCE_MAX_LEN + 128] = {0};
    char source[TEXT_MESSAGE_SOURCE_MAX_LEN] = "master-web";
    char message[TEXT_MESSAGE_MAX_LEN + 1] = "";
    int remaining = req->content_len;
    int received = 0;
    int timeout_count = 0;

    if (remaining <= 0)
    {
        httpd_resp_set_status(req, "400 Bad Request");
        status_web_set_api_headers(req);
        return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"message body is empty\"}");
    }

    if ((size_t)remaining >= sizeof(body))
    {
        httpd_resp_set_status(req, "413 Payload Too Large");
        status_web_set_api_headers(req);
        return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"message body too large\"}");
    }

    while (remaining > 0)
    {
        int ret = httpd_req_recv(req, body + received, remaining);

        if (ret == HTTPD_SOCK_ERR_TIMEOUT)
        {
            timeout_count++;
            if (timeout_count < 3)
            {
                continue;
            }

            httpd_resp_set_status(req, "408 Request Timeout");
            status_web_set_api_headers(req);
            return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"timeout reading message body\"}");
        }

        if (ret <= 0)
        {
            httpd_resp_set_status(req, "500 Internal Server Error");
            status_web_set_api_headers(req);
            return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"failed to read message body\"}");
        }

        received += ret;
        remaining -= ret;
    }
    body[received] = '\0';

    (void)text_message_extract_json_string(body, "source", source, sizeof(source));
    if (!text_message_extract_json_string(body, "message", message, sizeof(message)))
    {
        text_message_copy_clean(message, sizeof(message), body);
    }

    if (message[0] == '\0')
    {
        httpd_resp_set_status(req, "400 Bad Request");
        status_web_set_api_headers(req);
        return httpd_resp_sendstr(req, "{\"ok\":false,\"error\":\"message is empty\"}");
    }

    return status_web_message_store_and_respond(req, source, message);
}

static esp_err_t status_web_send_master_error(httpd_req_t *req,
                                              const char *status,
                                              const char *error)
{
    char error_json[TEXT_MESSAGE_SOURCE_MAX_LEN * 2] = "";
    char payload[256] = {0};
    int payload_len;

    text_message_json_escape(error_json, sizeof(error_json), error);
    payload_len = snprintf(payload,
                           sizeof(payload),
                           "{\"ok\":false,\"device\":\"" ATAK_COT_UID "\",\"error\":\"%s\"}",
                           error_json);

    httpd_resp_set_status(req, status);
    httpd_resp_set_type(req, "application/json");
    status_web_set_api_headers(req);
    return httpd_resp_send(req, payload, payload_len);
}

static bool text_message_parse_http_url(const char *url,
                                        char *host,
                                        size_t host_len,
                                        uint16_t *port,
                                        char *path,
                                        size_t path_len)
{
    const char *cursor;
    const char *slash;
    const char *host_end;
    const char *colon = NULL;
    size_t host_copy_len;
    size_t ii;

    if ((url == NULL) || (host == NULL) || (host_len == 0) ||
        (port == NULL) || (path == NULL) || (path_len == 0))
    {
        return false;
    }

    host[0] = '\0';
    path[0] = '\0';

    if (strncmp(url, "http://", 7) != 0)
    {
        return false;
    }

    cursor = url + 7;
    slash = strchr(cursor, '/');
    host_end = slash != NULL ? slash : cursor + strlen(cursor);

    if (host_end == cursor)
    {
        return false;
    }

    for (ii = 0; cursor + ii < host_end; ii++)
    {
        if (cursor[ii] == ':')
        {
            colon = cursor + ii;
        }
    }

    if (colon != NULL)
    {
        long parsed_port = strtol(colon + 1, NULL, 10);
        if ((parsed_port < 1) || (parsed_port > 65535))
        {
            return false;
        }
        *port = (uint16_t)parsed_port;
        host_end = colon;
    }
    else
    {
        *port = 80;
    }

    host_copy_len = (size_t)(host_end - cursor);
    if ((host_copy_len == 0) || (host_copy_len >= host_len))
    {
        return false;
    }

    memcpy(host, cursor, host_copy_len);
    host[host_copy_len] = '\0';

    (void)mmosal_safer_strcpy(path, slash != NULL ? slash : "/", path_len);
    return true;
}

static esp_err_t text_message_http_get_status(const char *url,
                                              int *http_status,
                                              uint32_t *latency_ms)
{
    char host[64] = "";
    char path[TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN] = "";
    char request[TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN + 160] = "";
    uint16_t port = 80;
    struct sockaddr_in dest_addr;
    struct timeval timeout = {
        .tv_sec = 15,
        .tv_usec = 0,
    };
    uint32_t started_ms;
    int sock = -1;
    int request_len;
    int sent = 0;
    int received;
    char response[96] = "";
    esp_err_t result = ESP_FAIL;

    if (http_status != NULL)
    {
        *http_status = 0;
    }

    if (latency_ms != NULL)
    {
        *latency_ms = 0;
    }

    if (!text_message_parse_http_url(url, host, sizeof(host), &port, path, sizeof(path)))
    {
        return ESP_ERR_INVALID_ARG;
    }

    memset(&dest_addr, 0, sizeof(dest_addr));
    dest_addr.sin_family = AF_INET;
    dest_addr.sin_port = htons(port);
    if (inet_pton(AF_INET, host, &dest_addr.sin_addr) != 1)
    {
        return ESP_ERR_INVALID_ARG;
    }

    request_len = snprintf(request,
                           sizeof(request),
                           "GET %s HTTP/1.1\r\n"
                           "Host: %s\r\n"
                           "Accept: application/json\r\n"
                           "Connection: close\r\n"
                           "\r\n",
                           path,
                           host);

    if ((request_len < 0) || (request_len >= (int)sizeof(request)))
    {
        return ESP_ERR_INVALID_SIZE;
    }

    started_ms = mmosal_get_time_ms();
    sock = socket(AF_INET, SOCK_STREAM, IPPROTO_IP);
    if (sock < 0)
    {
        goto cleanup;
    }

    (void)setsockopt(sock, SOL_SOCKET, SO_RCVTIMEO, &timeout, sizeof(timeout));
    (void)setsockopt(sock, SOL_SOCKET, SO_SNDTIMEO, &timeout, sizeof(timeout));

    if (connect(sock, (struct sockaddr *)&dest_addr, sizeof(dest_addr)) != 0)
    {
        goto cleanup;
    }

    while (sent < request_len)
    {
        int written = send(sock, request + sent, request_len - sent, 0);
        if (written <= 0)
        {
            goto cleanup;
        }
        sent += written;
    }

    received = recv(sock, response, sizeof(response) - 1, 0);
    if (received <= 0)
    {
        goto cleanup;
    }

    response[received] = '\0';
    if (sscanf(response, "HTTP/%*s %d", http_status) == 1)
    {
        result = ESP_OK;
    }

cleanup:
    if (sock >= 0)
    {
        close(sock);
    }

    if (latency_ms != NULL)
    {
        *latency_ms = mmosal_get_time_ms() - started_ms;
    }

    return result;
}

static esp_err_t status_web_send_master_handler(httpd_req_t *req)
{
    char master_url[TEXT_MESSAGE_MASTER_URL_MAX_LEN] = "";
    char source[TEXT_MESSAGE_SOURCE_MAX_LEN] = ATAK_COT_UID;
    char target[TEXT_MESSAGE_SOURCE_MAX_LEN] = "MASTER-RASPI-4";
    char source_encoded[(TEXT_MESSAGE_SOURCE_MAX_LEN * 3) + 1] = "";
    char target_encoded[(TEXT_MESSAGE_SOURCE_MAX_LEN * 3) + 1] = "";
    char source_json[TEXT_MESSAGE_SOURCE_MAX_LEN * 2] = "";
    char target_json[TEXT_MESSAGE_SOURCE_MAX_LEN * 2] = "";
    char master_url_json[TEXT_MESSAGE_MASTER_URL_MAX_LEN * 2] = "";
    char message[TEXT_MESSAGE_MAX_LEN + 1] = "";
    char *query = NULL;
    char *message_encoded = NULL;
    char *request_url = NULL;
    char *payload = NULL;
    esp_err_t err;
    esp_err_t send_err = ESP_OK;
    int master_status = 0;
    int payload_len;
    int request_len;
    uint32_t latency_ms;
    size_t query_len = httpd_req_get_url_query_len(req);

    if (query_len == 0)
    {
        return status_web_send_master_error(req,
                                            "400 Bad Request",
                                            "query master and message are required");
    }

    if (query_len >= TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN)
    {
        return status_web_send_master_error(req,
                                            "414 URI Too Long",
                                            "query string too long");
    }

    query = (char *)calloc(1, TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN);
    message_encoded = (char *)calloc(1, (TEXT_MESSAGE_MAX_LEN * 3) + 1);
    request_url = (char *)calloc(1, TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN);
    payload = (char *)calloc(1, TEXT_MESSAGE_RESPONSE_MAX_LEN);

    if ((query == NULL) || (message_encoded == NULL) || (request_url == NULL) || (payload == NULL))
    {
        send_err = status_web_send_master_error(req,
                                                "500 Internal Server Error",
                                                "not enough memory for send-master request");
        goto cleanup;
    }

    if (httpd_req_get_url_query_str(req, query, TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN) != ESP_OK)
    {
        send_err = status_web_send_master_error(req,
                                                "400 Bad Request",
                                                "failed to parse query string");
        goto cleanup;
    }

    if (httpd_query_key_value(query, "master", master_url, sizeof(master_url)) != ESP_OK)
    {
        if (httpd_query_key_value(query, "url", master_url, sizeof(master_url)) != ESP_OK)
        {
            (void)httpd_query_key_value(query, "inbox", master_url, sizeof(master_url));
        }
    }
    (void)httpd_query_key_value(query, "source", source, sizeof(source));
    (void)httpd_query_key_value(query, "target", target, sizeof(target));
    if (httpd_query_key_value(query, "message", message, sizeof(message)) != ESP_OK)
    {
        (void)httpd_query_key_value(query, "text", message, sizeof(message));
    }

    text_message_url_decode(master_url);
    text_message_url_decode(source);
    text_message_url_decode(target);
    text_message_url_decode(message);

    if (strncmp(master_url, "http://", 7) != 0)
    {
        send_err = status_web_send_master_error(req,
                                                "400 Bad Request",
                                                "master inbox must use http:// URL");
        goto cleanup;
    }

    if (message[0] == '\0')
    {
        send_err = status_web_send_master_error(req,
                                                "400 Bad Request",
                                                "message is empty");
        goto cleanup;
    }

    text_message_url_encode(source_encoded, sizeof(source_encoded), source);
    text_message_url_encode(target_encoded, sizeof(target_encoded), target);
    text_message_url_encode(message_encoded, (TEXT_MESSAGE_MAX_LEN * 3) + 1, message);

    request_len = snprintf(request_url,
                           TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN,
                           "%s%csource=%s&target=%s&message=%s&uptime_ms=%lu&rssi_dbm=%ld&firmware_version=%s",
                           master_url,
                           strchr(master_url, '?') == NULL ? '?' : '&',
                           source_encoded,
                           target_encoded,
                           message_encoded,
                           (unsigned long)mmosal_get_time_ms(),
                           (long)mmwlan_get_rssi(),
                           STATUS_WEB_FIRMWARE_VERSION);

    if ((request_len < 0) || (request_len >= TEXT_MESSAGE_MASTER_REQUEST_MAX_LEN))
    {
        send_err = status_web_send_master_error(req,
                                                "414 URI Too Long",
                                                "master inbox request URL too long");
        goto cleanup;
    }

    err = text_message_http_get_status(request_url, &master_status, &latency_ms);

    text_message_json_escape(source_json, sizeof(source_json), source);
    text_message_json_escape(target_json, sizeof(target_json), target);
    text_message_json_escape(master_url_json, sizeof(master_url_json), master_url);

    payload_len = snprintf(payload,
                           TEXT_MESSAGE_RESPONSE_MAX_LEN,
                           "{"
                           "\"ok\":%s,"
                           "\"device\":\"" ATAK_COT_UID "\","
                           "\"direction\":\"slave_to_master\","
                           "\"master_status\":%d,"
                           "\"latency_ms\":%lu,"
                           "\"esp_error\":\"%s\","
                           "\"source\":\"%s\","
                           "\"target\":\"%s\","
                           "\"bytes\":%u,"
                           "\"master_url\":\"%s\""
                           "}",
                           (err == ESP_OK && master_status >= 200 && master_status < 300) ? "true" : "false",
                           master_status,
                           (unsigned long)latency_ms,
                           esp_err_to_name(err),
                           source_json,
                           target_json,
                           (unsigned)strlen(message),
                           master_url_json);

    httpd_resp_set_type(req, "application/json");
    status_web_set_api_headers(req);
    send_err = httpd_resp_send(req, payload, payload_len);

cleanup:
    free(query);
    free(message_encoded);
    free(request_url);
    free(payload);
    return send_err;
}
#endif

static bool status_web_register_uri_checked(const char *label, const httpd_uri_t *uri)
{
    esp_err_t err = httpd_register_uri_handler(status_web_server, uri);

    if (err == ESP_OK)
    {
        printf("HTTP route registered: %s %s\n", label, uri->uri);
        return true;
    }

    printf("Failed to register HTTP route %s %s: %s\n",
           label,
           uri->uri,
           esp_err_to_name(err));
    return false;
}

static void start_status_web_server(void)
{
    httpd_config_t config = HTTPD_DEFAULT_CONFIG();
    httpd_uri_t root_uri = {
        .uri = "/",
        .method = HTTP_GET,
        .handler = status_web_root_handler,
        .user_ctx = NULL,
    };
    httpd_uri_t api_uri = {
        .uri = "/api/status*",
        .method = HTTP_GET,
        .handler = status_web_api_handler,
        .user_ctx = NULL,
    };
    httpd_uri_t api_options_uri = {
        .uri = "/api/status*",
        .method = HTTP_OPTIONS,
        .handler = status_web_options_handler,
        .user_ctx = NULL,
    };
#if TEXT_MESSAGE_ENABLE
    httpd_uri_t message_get_uri = {
        .uri = "/api/message*",
        .method = HTTP_GET,
        .handler = status_web_message_get_handler,
        .user_ctx = NULL,
    };
    httpd_uri_t message_post_uri = {
        .uri = "/api/message*",
        .method = HTTP_POST,
        .handler = status_web_message_post_handler,
        .user_ctx = NULL,
    };
    httpd_uri_t message_options_uri = {
        .uri = "/api/message*",
        .method = HTTP_OPTIONS,
        .handler = status_web_options_handler,
        .user_ctx = NULL,
    };
    httpd_uri_t send_master_get_uri = {
        .uri = "/api/send-master*",
        .method = HTTP_GET,
        .handler = status_web_send_master_handler,
        .user_ctx = NULL,
    };
    httpd_uri_t send_master_options_uri = {
        .uri = "/api/send-master*",
        .method = HTTP_OPTIONS,
        .handler = status_web_options_handler,
        .user_ctx = NULL,
    };
#endif
    struct mmipal_ip_config ip_config = MMIPAL_IP_CONFIG_DEFAULT;

    if (status_web_server != NULL)
    {
        return;
    }

    {
        esp_err_t err = esp_event_loop_create_default();
        if ((err != ESP_OK) && (err != ESP_ERR_INVALID_STATE))
        {
            printf("Failed to create default event loop for HTTP server, err=%s\n",
                   esp_err_to_name(err));
            return;
        }
    }

    config.server_port = STATUS_WEB_PORT;
    config.stack_size = 12288;
    config.max_uri_handlers = 12;
    config.max_open_sockets = 7;
    config.lru_purge_enable = true;
    config.recv_wait_timeout = 10;
    config.send_wait_timeout = 10;
    config.uri_match_fn = httpd_uri_match_wildcard;

#if TEXT_MESSAGE_ENABLE
    text_message_init();
#endif

    if (httpd_start(&status_web_server, &config) != ESP_OK)
    {
        printf("Failed to start embedded status web server on port %u\n",
               (unsigned)STATUS_WEB_PORT);
        status_web_server = NULL;
        return;
    }

    status_web_register_uri_checked("GET", &root_uri);
    status_web_register_uri_checked("GET", &api_uri);
    status_web_register_uri_checked("OPTIONS", &api_options_uri);
#if TEXT_MESSAGE_ENABLE
    status_web_register_uri_checked("GET", &message_get_uri);
    status_web_register_uri_checked("POST", &message_post_uri);
    status_web_register_uri_checked("OPTIONS", &message_options_uri);
    status_web_register_uri_checked("GET", &send_master_get_uri);
    status_web_register_uri_checked("OPTIONS", &send_master_options_uri);
    printf("Text message API enabled at /api/message and /api/send-master (firmware %s)\n",
           STATUS_WEB_FIRMWARE_VERSION);
#else
    printf("Text message API disabled at build time.\n");
#endif

    if ((mmipal_get_ip_config(&ip_config) == MMIPAL_SUCCESS) && (ip_config.ip_addr[0] != '\0'))
    {
        printf("Status web UI available at http://%s:%u/\n",
               ip_config.ip_addr,
               (unsigned)STATUS_WEB_PORT);
    }
    else
    {
        printf("Status web server started on port %u\n", (unsigned)STATUS_WEB_PORT);
    }
}
#endif

/**
 * Function to format a given number of bytes into an appropriate SI base. I.e if you give it 1400
 * it will return 1 with unit_index set to 1 for Kilo.
 *
 * @warning This uses power of 10 units (kilo, mega, giga, etc). Not to be confused with power of 2
 *          units (kibi, mebi, gibi, etc).
 *
 * @param[in]   bytes       Original number of bytes
 * @param[out]  unit_index  Index into the @ref units array. Must not be NULL
 *
 * @return Number of bytes formatted to the appropriate unit given by the unit index.
 */
static uint32_t format_bytes(uint64_t bytes, uint8_t *unit_index)
{
    MMOSAL_ASSERT(unit_index != NULL);
    *unit_index = 0;

    while (bytes >= 1000 && *unit_index < 4)
    {
        bytes /= 1000;
        (*unit_index)++;
    }

    return bytes;
}
/**
 * Handle a report at the end of an iperf transfer.
 *
 * @param report    The iperf report.
 * @param arg       Opaque argument specified when iperf was started.
 * @param handle    The iperf instance handle returned when iperf was started.
 */
static void iperf_report_handler(const struct mmiperf_report *report, void *arg,
                                 mmiperf_handle_t handle)
{
    (void)arg;
    (void)handle;

    uint8_t bytes_transferred_unit_index = 0;
    uint32_t bytes_transferred_formatted = format_bytes(report->bytes_transferred,
                                                        &bytes_transferred_unit_index);

    printf("\nIperf Report\n");
    printf("  Remote Address: %s:%d\n", report->remote_addr, report->remote_port);
    printf("  Local Address:  %s:%d\n", report->local_addr, report->local_port);
    printf("  Transferred: %lu %cBytes, duration: %lu ms, bandwidth: %lu kbps\n",
           bytes_transferred_formatted, units[bytes_transferred_unit_index],
           report->duration_ms, report->bandwidth_kbitpsec);
    printf("\n");
    printf("COMMAND_TIMING source=\"ESP32 local\" command_type=\"iperf\" "
           "command_sent_time_ms=%lu command_received_time_ms=%lu "
           "command_executed_time_ms=%lu execution_status=success\n",
           (unsigned long)iperf_command_sent_time_ms,
           (unsigned long)iperf_command_received_time_ms,
           (unsigned long)mmosal_get_time_ms());

    if ((report->report_type == MMIPERF_UDP_DONE_SERVER) ||
        (report->report_type == MMIPERF_TCP_DONE_SERVER))
    {
        printf("Waiting for client to connect...\n");
    }
}

/** Start iperf as a TCP client. */
static void start_tcp_client(void)
{
    uint32_t server_port = IPERF_SERVER_PORT;
    struct mmiperf_client_args args = MMIPERF_CLIENT_ARGS_DEFAULT;

    /* Get the Server IP */
    strncpy(args.server_addr, IPERF_SERVER_IP, sizeof(args.server_addr));


    MMOSAL_ASSERT(server_port <= UINT16_MAX);
    args.server_port = server_port;

    int amount = IPERF_TIME_AMOUNT;
    args.amount = amount;
    if (args.amount < 0)
    {
        args.amount *= 100;
    }
    args.report_fn = iperf_report_handler;

    mmiperf_start_tcp_client(&args);
    iperf_command_received_time_ms = mmosal_get_time_ms();
    printf("\nIperf TCP client started, waiting for completion...\n");
}

/** Start iperf as a UDP client. */
static void start_udp_client(void)
{
    uint32_t server_port = IPERF_SERVER_PORT;
    struct mmiperf_client_args args = MMIPERF_CLIENT_ARGS_DEFAULT;

    strncpy(args.server_addr, IPERF_SERVER_IP, sizeof(args.server_addr));

    MMOSAL_ASSERT(server_port <= UINT16_MAX);
    args.server_port = server_port;

    int amount = IPERF_TIME_AMOUNT;
    args.amount = amount;
    if (args.amount < 0)
    {
        args.amount *= 100;
    }
    args.report_fn = iperf_report_handler;

    mmiperf_start_udp_client(&args);
    iperf_command_received_time_ms = mmosal_get_time_ms();
    printf("\nIperf UDP client started, waiting for completion...\n");
}

/** Start iperf as a TCP server. */
static void start_tcp_server(void)
{
    struct mmiperf_server_args args = MMIPERF_SERVER_ARGS_DEFAULT;

    uint32_t local_port = IPERF_SERVER_PORT;
    args.local_port = (uint16_t) local_port;

    args.report_fn = iperf_report_handler;

    mmiperf_handle_t iperf_handle = mmiperf_start_tcp_server(&args);
    if (iperf_handle == NULL)
    {
        printf("Failed to get local address\n");
        printf("COMMAND_TIMING source=\"ESP32 local\" command_type=\"iperf\" "
               "command_sent_time_ms=%lu command_received_time_ms=%lu "
               "command_executed_time_ms=%lu execution_status=fail\n",
               (unsigned long)iperf_command_sent_time_ms,
               (unsigned long)mmosal_get_time_ms(),
               (unsigned long)mmosal_get_time_ms());
        return;
    }
    iperf_command_received_time_ms = mmosal_get_time_ms();
    printf("\nIperf TCP server started, waiting for client to connect...\n");
    struct mmipal_ip_config ip_config;
    enum mmipal_status status;
    status = mmipal_get_ip_config(&ip_config);
    if (status == MMIPAL_SUCCESS)
    {
        printf("Execute cmd on AP 'iperf -c %s -p %u -i 1' for IPv4\n",
               ip_config.ip_addr, args.local_port);
    }

    struct mmipal_ip6_config ip6_config;
    status = mmipal_get_ip6_config(&ip6_config);
    if (status == MMIPAL_SUCCESS)
    {
        printf("Execute cmd on AP 'iperf -c %s%%wlan0 -p %u -i 1 -V' for IPv6\n",
               ip6_config.ip6_addr[0], args.local_port);
    }
}

/** Start iperf as a UDP server. */
static void start_udp_server(void)
{
    struct mmiperf_server_args args = MMIPERF_SERVER_ARGS_DEFAULT;

    uint32_t local_port = IPERF_SERVER_PORT;
    args.local_port = (uint16_t) local_port;

    args.report_fn = iperf_report_handler;

    mmiperf_handle_t iperf_handle = mmiperf_start_udp_server(&args);
    if (iperf_handle == NULL)
    {
        printf("Failed to start iperf server\n");
        printf("COMMAND_TIMING source=\"ESP32 local\" command_type=\"iperf\" "
               "command_sent_time_ms=%lu command_received_time_ms=%lu "
               "command_executed_time_ms=%lu execution_status=fail\n",
               (unsigned long)iperf_command_sent_time_ms,
               (unsigned long)mmosal_get_time_ms(),
               (unsigned long)mmosal_get_time_ms());
        return;
    }
    iperf_command_received_time_ms = mmosal_get_time_ms();

    printf("\nIperf UDP server started, waiting for client to connect...\n");
    struct mmipal_ip_config ip_config;
    enum mmipal_status status;
    status = mmipal_get_ip_config(&ip_config);
    if (status == MMIPAL_SUCCESS)
    {
        printf("Execute cmd on AP 'iperf -c %s -p %u -i 1 -u -b 20M' for IPv4\n",
               ip_config.ip_addr, args.local_port);
    }

    struct mmipal_ip6_config ip6_config;
    status = mmipal_get_ip6_config(&ip6_config);
    if (status == MMIPAL_SUCCESS)
    {
        printf("Execute cmd on AP 'iperf -c %s%%wlan0 -p %u -i 1 -V -u -b 20M' for IPv6\n",
               ip6_config.ip6_addr[0], args.local_port);
    }
}

/**
 * Main entry point to the application. This will be invoked in a thread once operating system
 * and hardware initialization has completed. It may return, but it does not have to.
 */
void app_main(void)
{
    printf("\n\nMorse Iperf Demo (Built " __DATE__ " " __TIME__ ")\n\n");

    /* Initialize and connect to Wi-Fi, blocks till connected */
    app_wlan_init();
    app_wlan_start();

#if ATAK_COT_ENABLE && ATAK_GPS_ENABLE
    start_atak_gps();
#endif

#if BMP180_ENABLE
    start_bmp180();
#endif

#if STATUS_WEB_ENABLE
    start_status_web_server();
#endif

#if ATAK_COT_ENABLE
    start_atak_cot();
#endif

    enum iperf_type iperf_mode = IPERF_TYPE;
    iperf_command_sent_time_ms = mmosal_get_time_ms();
    iperf_command_received_time_ms = iperf_command_sent_time_ms;

#if IPERF_AUTOSTART
    switch (iperf_mode)
    {
    case IPERF_TCP_SERVER:
        start_tcp_server();
        break;

    case IPERF_UDP_SERVER:
        start_udp_server();
        break;

    case IPERF_UDP_CLIENT:
        start_udp_client();
        break;

    case IPERF_TCP_CLIENT:
        start_tcp_client();
        break;
    }
#else
    (void)iperf_mode;
    printf("iperf autostart disabled (IPERF_AUTOSTART=0). HaLow airtime fokus untuk GPS+CoT+HTTP.\n");
#endif

    while (true)
    {
        app_wlan_arp_send();
        mmosal_task_sleep(5000);
    }
}

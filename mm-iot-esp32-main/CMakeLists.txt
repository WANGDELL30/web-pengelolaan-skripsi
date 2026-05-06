# ESP-IDF wrapper for opening this outer workspace folder directly in VS Code.
cmake_minimum_required(VERSION 3.16)

set(MMIOT_REPO_ROOT "${CMAKE_CURRENT_LIST_DIR}/mm-iot-esp32-main")
get_filename_component(MMIOT_REPO_ROOT "${MMIOT_REPO_ROOT}" ABSOLUTE)
set(MMIOT_EXAMPLE_DIR "${MMIOT_REPO_ROOT}/examples/iperf")

if(NOT EXISTS "${MMIOT_EXAMPLE_DIR}/main/CMakeLists.txt")
    message(FATAL_ERROR "Cannot find iperf example at: ${MMIOT_EXAMPLE_DIR}")
endif()

if(NOT DEFINED ENV{IDF_PATH} OR "$ENV{IDF_PATH}" STREQUAL "")
    set(_default_idf_path "C:/Espressif/frameworks/esp-idf-v5.1.1")
    if(EXISTS "${_default_idf_path}/tools/cmake/project.cmake")
        set(ENV{IDF_PATH} "${_default_idf_path}")
    else()
        message(FATAL_ERROR "IDF_PATH environment variable not defined. Configure ESP-IDF v5.1.1 first.")
    endif()
endif()

set(ENV{MMIOT_ROOT} "${MMIOT_REPO_ROOT}")

if(NOT DEFINED IDF_TARGET)
    set(IDF_TARGET esp32s3)
endif()

set(SDKCONFIG "${MMIOT_EXAMPLE_DIR}/sdkconfig")
set(SDKCONFIG_DEFAULTS "${MMIOT_EXAMPLE_DIR}/sdkconfig.defaults")

set(EXTRA_COMPONENT_DIRS
    "${MMIOT_EXAMPLE_DIR}/main"
    "${MMIOT_REPO_ROOT}/framework/mm_shims"
    "${MMIOT_REPO_ROOT}/framework/morselib"
    "${MMIOT_REPO_ROOT}/framework/src/mmipal"
    "${MMIOT_REPO_ROOT}/framework/src/mmiperf"
    "${MMIOT_REPO_ROOT}/framework/src/mmpktmem"
    "${MMIOT_REPO_ROOT}/framework/src/mmutils")

message(STATUS "MMIOT_ROOT: $ENV{MMIOT_ROOT}")
message(STATUS "ESP-IDF example: ${MMIOT_EXAMPLE_DIR}")

# Workaround to allow link status callbacks in LWIP.
add_compile_definitions(LWIP_NETIF_LINK_CALLBACK=1)

if(DEFINED COUNTRY_CODE)
    message(STATUS "Using country code: ${COUNTRY_CODE}")
    add_compile_definitions(COUNTRY_CODE="${COUNTRY_CODE}")
endif()

include($ENV{IDF_PATH}/tools/cmake/project.cmake)
project(iperf)

-- Store slave-to-master text messages received by the master inbox endpoint.
CREATE TABLE IF NOT EXISTS text_message_inbox_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    received_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    source_node VARCHAR(50),
    target_node_id VARCHAR(50),
    source_ip VARCHAR(45),
    message_text TEXT NOT NULL,
    raw_payload TEXT,
    rssi_dbm INT NULL,
    slave_uptime_ms BIGINT NULL,
    delivery_status ENUM('success', 'fail') DEFAULT 'success',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

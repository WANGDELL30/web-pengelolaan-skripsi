-- Store master-to-slave text message delivery attempts.
CREATE TABLE IF NOT EXISTS text_message_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    test_date DATE NOT NULL,
    source_node VARCHAR(50),
    target_node_id VARCHAR(50),
    target_ip VARCHAR(45) NOT NULL,
    target_port INT DEFAULT 80,
    protocol ENUM('HTTP') DEFAULT 'HTTP',
    endpoint VARCHAR(120) DEFAULT '/api/message',
    message_text TEXT NOT NULL,
    request_payload TEXT,
    response_status_code INT,
    response_body TEXT,
    latency_ms DECIMAL(10,2),
    delivery_status ENUM('success', 'fail') DEFAULT 'fail',
    error_message TEXT,
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

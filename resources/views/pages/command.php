<?php
$pageConfig = [
    'title' => 'Command Execution',
    'icon' => 'fas fa-terminal',
    'description' => 'Input pengujian pengiriman dan eksekusi command ke node target.',
    'table' => 'command_execution_tests',
    'order' => 'test_date DESC, created_at DESC',
    'chart_label_fields' => ['command_type', 'target_node_id'],
    'chart_label_caption' => 'Label grafik: command - target node',
    'chart_metrics' => [
        ['field' => 'command_delivery_delay', 'label' => 'Delivery Delay', 'unit' => 'ms', 'type' => 'bar'],
        ['field' => 'command_execution_delay', 'label' => 'Execution Delay', 'unit' => 'ms', 'type' => 'bar'],
        ['field' => 'total_command_time', 'label' => 'Total Command Time', 'unit' => 'ms', 'type' => 'line'],
        ['field' => 'command_success_rate', 'label' => 'Success Rate', 'unit' => '%', 'type' => 'bar'],
    ],
    'chart_status_field' => 'execution_status',
    'chart_notes' => [
        'Delivery delay membaca waktu command sampai ke node.',
        'Execution delay membaca waktu node mengeksekusi command setelah diterima.',
    ],
    'fields' => [
        ['name' => 'test_date', 'label' => 'Test Date', 'type' => 'date', 'required' => true],
        ['name' => 'command_type', 'label' => 'Command Type', 'type' => 'select', 'options' => [
            'iperf' => 'iperf',
            'porting assistant' => 'porting assistant',
            'rf-test' => 'rf-test',
            'scan' => 'scan',
            'sta connect' => 'sta connect',
            'sta reboot' => 'sta reboot',
            'transfer reset' => 'transfer reset',
            'web camera server' => 'web camera server',
            'ping' => 'ping',
            'status' => 'status',
            'led on' => 'led on',
            'led off' => 'led off',
            'led toggle' => 'led toggle',
            'restart' => 'restart',
        ]],
        ['name' => 'source', 'label' => 'Source', 'required' => true],
        ['name' => 'target_node_id', 'label' => 'Target Node ID', 'required' => true],
        ['name' => 'command_sent_time_ms', 'label' => 'Command Sent Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'command_received_time_ms', 'label' => 'Command Received Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'command_executed_time_ms', 'label' => 'Command Executed Time (ms)', 'type' => 'number', 'integer' => true],
        ['name' => 'execution_status', 'label' => 'Execution Status', 'type' => 'select', 'options' => ['success', 'fail']],
        ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea'],
    ],
    'calculate' => function ($data) {
        $sent = (int) ($data['command_sent_time_ms'] ?? 0);
        $received = (int) ($data['command_received_time_ms'] ?? 0);
        $executed = (int) ($data['command_executed_time_ms'] ?? 0);

        return [
            'command_delivery_delay' => max(0, $received - $sent),
            'command_execution_delay' => max(0, $executed - $received),
            'total_command_time' => max(0, $executed - $sent),
            'command_success_rate' => ($data['execution_status'] ?? '') === 'success' ? 100 : 0,
        ];
    },
    'formulas' => [
        'Delivery Delay = Received Time - Sent Time',
        'Execution Delay = Executed Time - Received Time',
        'Total Command Time = Executed Time - Sent Time',
    ],
    'columns' => [
        ['label' => 'Date', 'field' => 'test_date', 'format' => 'date'],
        ['label' => 'Command', 'field' => 'command_type'],
        ['label' => 'Source', 'field' => 'source'],
        ['label' => 'Target', 'field' => 'target_node_id'],
        ['label' => 'Delivery', 'field' => 'command_delivery_delay', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Total', 'field' => 'total_command_time', 'decimals' => 2, 'suffix' => ' ms'],
        ['label' => 'Status', 'field' => 'execution_status', 'format' => 'status'],
    ],
];

?>

<div class="content-section">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="mb-1"><i class="fas fa-satellite-dish"></i> Live Slave Control</h4>
            <p class="text-muted mb-0">Kirim command UDP dari website ke firmware `slave_command` melalui jaringan Wi-Fi HaLow.</p>
        </div>
        <span class="badge bg-primary">API: slave_command_api.php</span>
    </div>

    <div class="row">
        <div class="col-xl-7 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-terminal"></i> Kirim Command ke Slave</h6>
                </div>
                <div class="card-body">
                    <form id="slaveCommandForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">IP Slave</label>
                                <input type="text" class="form-control" name="slave_ip" value="192.168.1.113" placeholder="192.168.1.113" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">UDP Port</label>
                                <input type="number" class="form-control" name="port" value="5555" min="1" max="65535" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label">Timeout (ms)</label>
                                <input type="number" class="form-control" name="timeout_ms" value="3000" min="500" max="10000" step="500" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Target Node ID</label>
                                <input type="text" class="form-control" name="target_node_id" value="SLAVE_001" maxlength="50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Source</label>
                                <input type="text" class="form-control" name="source" value="WEB_MASTER" maxlength="50" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Token</label>
                                <input type="password" class="form-control" name="token" value="halow123" maxlength="64" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Command</label>
                                <select class="form-select" name="command" id="slaveCommandSelect" required>
                                    <option value="STATUS">STATUS</option>
                                    <option value="PING">PING</option>
                                    <option value="LED">LED</option>
                                    <option value="RESTART">RESTART</option>
                                    <option value="HELP">HELP</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label">Argumen</label>
                                <select class="form-select" name="argument" id="slaveCommandArgument">
                                    <option value="">-</option>
                                    <option value="ON">ON</option>
                                    <option value="OFF">OFF</option>
                                    <option value="TOGGLE">TOGGLE</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary" id="slaveCommandSubmit">
                                <i class="fas fa-paper-plane"></i> Kirim Command
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="slaveCommandStatus">
                                <i class="fas fa-signal"></i> Quick Status
                            </button>
                            <button type="button" class="btn btn-outline-warning" id="slaveCommandRestart">
                                <i class="fas fa-power-off"></i> Quick Restart
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-5 mb-4">
            <div class="card h-100">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-reply"></i> Response Slave</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-secondary mb-3" id="slaveCommandStatusBox">Belum ada command dikirim.</div>
                    <pre class="bg-dark text-light rounded p-3 mb-0" id="slaveCommandResponse" style="min-height: 220px; white-space: pre-wrap;">-</pre>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    var $form = $('#slaveCommandForm');
    var $submit = $('#slaveCommandSubmit');
    var $statusBox = $('#slaveCommandStatusBox');
    var $response = $('#slaveCommandResponse');
    var $command = $('#slaveCommandSelect');
    var $argument = $('#slaveCommandArgument');

    function syncArgumentState() {
        var isLed = $command.val() === 'LED';
        $argument.prop('disabled', !isLed);
        if (!isLed) {
            $argument.val('');
        } else if (!$argument.val()) {
            $argument.val('ON');
        }
    }

    function formPayload() {
        var payload = {};
        $form.serializeArray().forEach(function(field) {
            payload[field.name] = field.value;
        });
        return payload;
    }

    async function sendSlaveCommand(override) {
        var payload = Object.assign(formPayload(), override || {});
        $submit.prop('disabled', true);
        $statusBox.removeClass('alert-success alert-danger alert-warning alert-secondary').addClass('alert-info').text('Mengirim command ke slave...');
        $response.text(JSON.stringify(payload, null, 2));

        try {
            var res = await fetch('slave_command_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            var data = await res.json();
            var ok = res.ok && data.status === 'success';
            $statusBox.removeClass('alert-info').addClass(ok ? 'alert-success' : 'alert-warning').text(data.message || (ok ? 'Command sukses.' : 'Command gagal.'));
            $response.text(JSON.stringify(data, null, 2));
        } catch (error) {
            $statusBox.removeClass('alert-info').addClass('alert-danger').text('Gagal memanggil API command.');
            $response.text(String(error));
        } finally {
            $submit.prop('disabled', false);
        }
    }

    $command.on('change', syncArgumentState);
    syncArgumentState();

    $form.on('submit', function(event) {
        event.preventDefault();
        sendSlaveCommand();
    });

    $('#slaveCommandStatus').on('click', function() {
        $command.val('STATUS').trigger('change');
        sendSlaveCommand({ command: 'STATUS', argument: '' });
    });

    $('#slaveCommandRestart').on('click', function() {
        if (!confirm('Kirim command RESTART ke slave?')) {
            return;
        }
        $command.val('RESTART').trigger('change');
        sendSlaveCommand({ command: 'RESTART', argument: '' });
    });
});
</script>

<?php
include __DIR__ . '/_test_page.php';

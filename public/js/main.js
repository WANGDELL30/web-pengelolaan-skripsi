/**
 * Main JavaScript for WiFi HaLow Testing System
 * Design and Implementation of a Wi-Fi HaLow-Based Tactical Monitoring and Communication Support System
 */

// Initialize when DOM is ready
$(document).ready(function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize all popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    function slugifyFilename(value) {
        return (value || 'grafik')
            .toString()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'grafik';
    }

    function downloadChartImage(canvas, filename) {
        if (!canvas) return;

        var exportCanvas = document.createElement('canvas');
        exportCanvas.width = canvas.width;
        exportCanvas.height = canvas.height;

        var context = exportCanvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, exportCanvas.width, exportCanvas.height);
        context.drawImage(canvas, 0, 0);

        var link = document.createElement('a');
        link.href = exportCanvas.toDataURL('image/png');
        link.download = slugifyFilename(filename) + '.png';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    function addChartDownloadButtons() {
        $('canvas').each(function() {
            var canvas = this;
            if (!canvas.id || $('.chart-download-btn[data-chart-target="' + canvas.id + '"]').length > 0) {
                return;
            }

            var card = $(canvas).closest('.card');
            var header = card.find('.card-header').first();
            var chartTitle = $.trim(header.find('h6, h5, h4').first().text()) || canvas.id;
            var button = $(
                '<button type="button" class="btn btn-outline-primary btn-sm chart-download-btn" ' +
                'data-chart-target="' + canvas.id + '" title="Download grafik sebagai gambar">' +
                '<i class="fas fa-download"></i> PNG</button>'
            );

            if (header.length) {
                header.addClass('d-flex justify-content-between align-items-center gap-2');
                header.append(button);
            } else {
                $(canvas).before($('<div class="text-end mb-2"></div>').append(button));
            }

            button.attr('data-chart-name', chartTitle);
        });
    }

    function enhanceResponsiveTable(table) {
        var $table = $(table);
        if (
            !$table.length ||
            !$table.find('thead th').length ||
            $table.closest('.modal').length ||
            $table.hasClass('report-matrix') ||
            $table.hasClass('keep-scroll-table')
        ) {
            return;
        }

        $table.addClass('mobile-card-table');
        var $shell = $table.closest('.table-responsive');
        if ($shell.length) {
            $shell.addClass('mobile-card-table-shell');
            if (!$shell.prev('.mobile-table-guide').length) {
                $(
                    '<div class="mobile-table-guide" aria-hidden="true">' +
                        '<i class="fas fa-mobile-screen-button"></i>' +
                        '<span>Data ditampilkan sebagai kartu agar setiap nilai mudah dibaca di layar ponsel.</span>' +
                    '</div>'
                ).insertBefore($shell);
            }
        }

        var headers = [];
        $table.find('thead tr').last().find('th').each(function() {
            headers.push($.trim($(this).text()).replace(/\s+/g, ' '));
        });

        $table.find('tbody tr').each(function() {
            $(this).children('td').each(function(index) {
                var $cell = $(this);
                if (!$cell.attr('colspan')) {
                    $cell.attr('data-label', headers[index] || 'Data');
                }
            });
        });
    }

    function enhanceResponsiveTables() {
        $('.table-responsive table, table.data-table, table.test-data-table, table.user-data-table').each(function() {
            enhanceResponsiveTable(this);
        });
    }

    $(document).on('click', '.chart-download-btn', function() {
        var target = $(this).data('chart-target');
        var canvas = document.getElementById(target);
        var title = $(this).data('chart-name') || $(this).closest('.card').find('.card-header h6, .card-header h5, .card-header h4').first().text() || target;
        downloadChartImage(canvas, title);
    });
    
    var sidebar = document.querySelector('.sidebar');
    var sidebarResizer = document.querySelector('.sidebar-resizer');
    var sidebarHideButton = document.querySelector('.sidebar-hide-btn');
    var layoutToggleButton = document.querySelector('.layout-toggle-btn');
    var root = document.documentElement;
    var layoutVersion = 'layout6-responsive';
    var compactLayoutBreakpoint = 992;
    var visualResizeTimer = null;

    function isCompactLayout() {
        return window.innerWidth < compactLayoutBreakpoint;
    }

    function syncSidebarAccessibility(isOpen) {
        $('.layout-toggle-btn, .mobile-menu-btn').attr('aria-expanded', isOpen ? 'true' : 'false');
        $('.sidebar-backdrop').attr('aria-hidden', isOpen ? 'false' : 'true');
    }

    function scheduleVisualResize() {
        window.clearTimeout(visualResizeTimer);
        visualResizeTimer = window.setTimeout(function() {
            if (window.Chart && Chart.instances) {
                Object.keys(Chart.instances).forEach(function(key) {
                    var chart = Chart.instances[key];
                    if (chart && typeof chart.resize === 'function') {
                        chart.resize();
                    }
                });
            }

            window.dispatchEvent(new CustomEvent('wifi:layoutchange'));
        }, 280);
    }

    function closeSidebar() {
        $('.sidebar').removeClass('show');
        $('.sidebar-backdrop').removeClass('show');
        $('body').removeClass('sidebar-open');
        syncSidebarAccessibility(!isCompactLayout() && !document.body.classList.contains('sidebar-hidden'));
        scheduleVisualResize();
    }

    function setMobileSidebarOpen(isOpen) {
        $('.sidebar').toggleClass('show', isOpen);
        $('.sidebar-backdrop').toggleClass('show', isOpen);
        $('body').toggleClass('sidebar-open', isOpen);
        syncSidebarAccessibility(isOpen);
        scheduleVisualResize();

        if (isOpen) {
            window.setTimeout(function() {
                var activeLink = document.querySelector('.sidebar .nav-link.active');
                if (activeLink) {
                    activeLink.focus({ preventScroll: true });
                }
            }, 260);
        }
    }

    if (localStorage.getItem('wifiLayoutVersion') !== layoutVersion) {
        localStorage.removeItem('sidebarStatus');
        localStorage.removeItem('wifiSidebarHidden');
        localStorage.removeItem('wifiSidebarWidth');
        localStorage.setItem('wifiLayoutVersion', layoutVersion);
    }

    function getSidebarLimits() {
        return {
            min: 280,
            max: 280
        };
    }

    function applySidebarWidth(width) {
        var limits = getSidebarLimits();
        var nextWidth = Math.max(limits.min, Math.min(limits.max, width));
        root.style.setProperty('--sidebar-width', nextWidth + 'px');
        
        // Also update the actual width of the sidebar element
        if (sidebar && !document.body.classList.contains('sidebar-hidden')) {
            sidebar.style.width = nextWidth + 'px';
        }
        
        localStorage.setItem('wifiSidebarWidth', String(nextWidth));
    }

    function restoreSidebarWidth() {
        if (!isCompactLayout()) {
            applySidebarWidth(280);
        }
    }

    function setSidebarHidden(isHidden) {
        if (isCompactLayout()) {
            return;
        }

        document.body.classList.toggle('sidebar-hidden', isHidden);
        localStorage.setItem('wifiSidebarHidden', isHidden ? '1' : '0');

        if (isHidden) {
            $('.sidebar').removeClass('show is-resizing');
            $('.sidebar-backdrop').removeClass('show');
            $('body').removeClass('sidebar-open sidebar-resizing');
        } else {
            restoreSidebarWidth();
        }

        if (layoutToggleButton) {
            layoutToggleButton.innerHTML = isHidden ? '<i class="fas fa-bars"></i>' : '<i class="fas fa-table-columns"></i>';
            layoutToggleButton.title = isHidden ? 'Tampilkan sidebar' : 'Sembunyikan sidebar';
        }

        syncSidebarAccessibility(!isHidden);
        scheduleVisualResize();
    }

    function restoreSidebarState() {
        localStorage.removeItem('sidebarStatus');

        if (isCompactLayout()) {
            document.body.classList.remove('sidebar-hidden');
            syncSidebarAccessibility(false);
            return;
        }

        restoreSidebarWidth();
        setSidebarHidden(localStorage.getItem('wifiSidebarHidden') === '1');
    }

    restoreSidebarWidth();
    restoreSidebarState();

    if (sidebar && sidebarResizer) {
        sidebarResizer.addEventListener('pointerdown', function(event) {
            if (isCompactLayout() || document.body.classList.contains('sidebar-hidden')) return;

            event.preventDefault();
            sidebar.classList.add('is-resizing');
            document.body.classList.add('sidebar-resizing');
            try {
                sidebarResizer.setPointerCapture(event.pointerId);
            } catch (error) {}

            var startX = event.clientX;
            var startWidth = sidebar.getBoundingClientRect().width;

            var onPointerMove = function(moveEvent) {
                applySidebarWidth(startWidth + (moveEvent.clientX - startX));
            };

            var onPointerUp = function(upEvent) {
                sidebar.classList.remove('is-resizing');
                document.body.classList.remove('sidebar-resizing');
                try {
                    sidebarResizer.releasePointerCapture(upEvent.pointerId);
                } catch (error) {}
                window.removeEventListener('pointermove', onPointerMove);
                window.removeEventListener('pointerup', onPointerUp);
                window.removeEventListener('pointercancel', onPointerUp);
            };

            window.addEventListener('pointermove', onPointerMove);
            window.addEventListener('pointerup', onPointerUp);
            window.addEventListener('pointercancel', onPointerUp);
        });
    }

    if (sidebarHideButton) {
        sidebarHideButton.addEventListener('click', function() {
            setSidebarHidden(true);
        });
    }

    if (layoutToggleButton) {
        layoutToggleButton.addEventListener('click', function() {
            if (isCompactLayout()) {
                var isOpen = !$('.sidebar').hasClass('show');
                setMobileSidebarOpen(isOpen);
                return;
            }

            setSidebarHidden(!document.body.classList.contains('sidebar-hidden'));
        });
    }

    var wasCompactLayout = isCompactLayout();
    $(window).on('resize', function() {
        var compactLayout = isCompactLayout();
        if (!compactLayout) {
            restoreSidebarState();
        } else {
            document.body.classList.remove('sidebar-hidden sidebar-resizing');
            if (!wasCompactLayout) {
                closeSidebar();
            }
        }
        wasCompactLayout = compactLayout;
    });

    // Sidebar toggle for mobile
    $('.mobile-menu-btn').on('click', function() {
        var isOpen = !$('.sidebar').hasClass('show');
        setMobileSidebarOpen(isOpen);
    });

    $('.sidebar-close-btn, .sidebar-backdrop').on('click', closeSidebar);

    $('.sidebar .nav-link').on('click', function() {
        if (isCompactLayout()) {
            closeSidebar();
        }
    });
    
    // Close sidebar when clicking outside on mobile
    $(document).on('click', function(event) {
        if (!$(event.target).closest('.sidebar').length && 
            !$(event.target).closest('.mobile-menu-btn').length &&
            !$(event.target).closest('.layout-toggle-btn').length &&
            isCompactLayout()) {
            closeSidebar();
        }
    });

    $(document).on('keydown', function(event) {
        if (event.key === 'Escape' && isCompactLayout() && $('.sidebar').hasClass('show')) {
            closeSidebar();
            if (layoutToggleButton) {
                layoutToggleButton.focus();
            }
        }
    });
    
    // Smooth scroll for anchor links (hanya untuk anchor dalam halaman yang sama)
    $('a[href^="#"]:not([href="#"])').on('click', function(e) {
        var target = $(this).attr('href');
        if ($(target).length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $(target).offset().top - 70
            }, 500, 'swing');
        }
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        var isValid = true;
        $(this).find('.required').each(function() {
            if ($(this).val() === '') {
                isValid = false;
                $(this).addClass('is-invalid');
            } else {
                $(this).removeClass('is-invalid');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            showToast('Mohon lengkapi semua field yang wajib diisi!', 'error');
        }
    });
    
    // Auto-calculate packet loss for connectivity tests
    $('#packet_sent, #packet_received').on('change', function() {
        var sent = parseInt($('#packet_sent').val()) || 0;
        var received = parseInt($('#packet_received').val()) || 0;
        var lost = sent - received;
        var lossPercent = sent > 0 ? ((lost / sent) * 100).toFixed(2) : 0;
        var successRate = sent > 0 ? ((received / sent) * 100).toFixed(2) : 0;
        
        $('#calc-results').html(
            '<div class="alert alert-success">' +
            '<strong>Hasil Perhitungan:</strong><br>' +
            'Packet Lost: ' + lost + '<br>' +
            'Packet Loss: ' + lossPercent + '%<br>' +
            'Success Rate: ' + successRate + '%' +
            '</div>'
        );
    });
    
    // Auto-calculate for range tests
    $('#range_test_form').on('submit', function(e) {
        calculateRangeTest();
    });
    
    // Auto-calculate for power tests
    $('#power_test_form').on('submit', function(e) {
        calculatePowerTest();
    });
    
    // Input mask for numeric fields
    $('input[type="number"]').on('input', function() {
        var value = parseFloat($(this).val());
        if (isNaN(value)) {
            $(this).val('');
        }
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var input = $($(this).data('target'));
        var icon = $(this).find('i');
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Confirm delete dialog
    $('.confirm-delete').on('click', function(e) {
        if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
            e.preventDefault();
        }
    });
    
    // Export buttons
    $('.btn-export').on('click', function() {
        var format = $(this).data('format');
        var type = $(this).data('type');
        exportData(type, format);
    });
    
    // Refresh data button
    $('.btn-refresh').on('click', function() {
        location.reload();
    });
    
    // Chart hover effects
    $('canvas').on('mouseenter', function() {
        $(this).closest('.card').addClass('shadow-custom');
    }).on('mouseleave', function() {
        $(this).closest('.card').removeClass('shadow-custom');
    });
    
    // Table row click
    $('.clickable-row').on('click', function() {
        window.location = $(this).data('href');
    });
    
    // Staggered animation for metrics
    $('.metric-item').each(function(i) {
        $(this).delay(i * 100).queue(function(next) {
            $(this).addClass('fade-in-up');
            next();
        });
    });
    
    // Scroll reveal animation
    function scrollReveal() {
        $('.js-scroll').each(function() {
            if (isElementInViewport(this)) {
                $(this).addClass('scrolled');
            }
        });
    }
    
    $(window).on('scroll', scrollReveal);
    scrollReveal();
    
    // Initialize all modals
    initializeModals();
    
    // Initialize all charts
    initializeCharts();
    
    // Initialize data tables
    initializeDataTables();

    // Keep full data readable on phones by labelling every value.
    enhanceResponsiveTables();
    $(document).on('init.dt.responsiveTable draw.dt.responsiveTable', function(event, settings) {
        if (settings && settings.nTable) {
            enhanceResponsiveTable(settings.nTable);
        }
    });
    setTimeout(enhanceResponsiveTables, 300);

    // Add PNG download buttons to every Chart.js canvas.
    addChartDownloadButtons();
    setTimeout(addChartDownloadButtons, 250);
    
    console.log('WiFi HaLow Testing System initialized successfully!');
});

/**
 * Calculate range test results
 */
function calculateRangeTest() {
    var x = parseFloat($('#coordinate_x').val()) || 0;
    var y = parseFloat($('#coordinate_y').val()) || 0;
    var z = parseFloat($('#coordinate_z').val()) || 0;
    
    // Calculate 3D distance
    var distance3D = Math.sqrt(x*x + y*y + z*z).toFixed(2);
    $('#distance_3d').val(distance3D);
    
    var distanceKm = (distance3D / 1000).toFixed(4);
    $('#distance_km').val(distanceKm);
    
    // Calculate FSPL
    var frequency = parseFloat($('#frequency').val()) || 915;
    var fspl = 32.44 + 20 * Math.log10(frequency) + 20 * Math.log10(distanceKm);
    $('#fspl').val(fspl.toFixed(2));
    
    // Calculate signal margin
    var rssi = parseFloat($('#rssi').val()) || 0;
    var sensitivity = parseFloat($('#sensitivity').val()) || -90;
    var margin = rssi - sensitivity;
    $('#signal_margin').val(margin.toFixed(2));
    
    // Determine status
    var snr = parseFloat($('#snr').val()) || 0;
    var packetLoss = parseFloat($('#packet_loss').val()) || 0;
    var status = determineRangeStatus(snr, packetLoss);
    $('#status_result').val(status);
}

/**
 * Calculate power test results
 */
function calculatePowerTest() {
    var voltage = parseFloat($('#battery_voltage').val()) || 0;
    var current = parseFloat($('#current').val()) || 0;
    var hours = parseFloat($('#test_duration').val()) || 0;
    var capacityMah = parseFloat($('#battery_capacity').val()) || 0;
    
    // Calculate power
    var power = (voltage * current).toFixed(2);
    $('#power').val(power);
    
    // Calculate energy
    var energy = (power * hours).toFixed(4);
    $('#energy').val(energy);
    
    // Calculate battery capacity in Wh
    var capacityWh = ((voltage * capacityMah) / 1000).toFixed(4);
    $('#capacity_wh').val(capacityWh);
    
    // Calculate estimated runtime
    if (power > 0) {
        var runtime = (capacityWh / power).toFixed(2);
        $('#runtime').val(runtime);
        
        var runtimeDays = (runtime / 24).toFixed(2);
        $('#runtime_days').val(runtimeDays);
    }
}

/**
 * Determine range status
 */
function determineRangeStatus(snr, packetLoss) {
    if (snr > 20 && packetLoss < 5) return 'good';
    if (snr >= 10 && snr <= 20) return 'moderate';
    return 'poor';
}

/**
 * Export data — mengarahkan ke export_excel.php yang sebenarnya
 */
function exportData(type, format) {
    // Map nama tipe ke nama tabel database
    var tableMap = {
        'connectivity':  'connectivity_tests',
        'connectivity_tests': 'connectivity_tests',
        'range':         'range_tests',
        'range_tests':   'range_tests',
        'penetration':   'signal_penetration_tests',
        'signal_penetration_tests': 'signal_penetration_tests',
        'latency':       'latency_tests',
        'latency_tests': 'latency_tests',
        'throughput':    'throughput_tests',
        'throughput_tests': 'throughput_tests',
        'interference':  'interference_tests',
        'interference_tests': 'interference_tests',
        'camera':        'slave_camera_tests',
        'slave_camera_tests': 'slave_camera_tests',
        'power':         'power_consumption_tests',
        'power_consumption_tests': 'power_consumption_tests',
        'command':       'command_execution_tests',
        'command_execution_tests': 'command_execution_tests',
        'text_message':  'text_message_logs',
        'text_message_logs': 'text_message_logs',
        'response':      'response_time_tests',
        'response_time_tests': 'response_time_tests',
        'encryption':    'encryption_tests',
        'encryption_tests': 'encryption_tests',
        'analysis':      'analysis_key_metrics',
        'reports':       'generated_reports',
        'generated_reports': 'generated_reports'
    };

    var table = tableMap[type] || type;

    if (format === 'xlsx' || format === 'excel') {
        showToast('Mengekspor ' + type + ' sebagai Excel...', 'info');
        window.location.href = 'export_excel.php?table=' + encodeURIComponent(table);
    } else if (format === 'csv') {
        // Cari tabel di halaman dan export sebagai CSV
        var tableEl = $('table[id]').first();
        if (tableEl.length) {
            exportCSV(tableEl.attr('id'), type + '_export.csv');
            showToast('Mengekspor ' + type + ' sebagai CSV...', 'info');
        } else {
            // Fallback ke Excel jika tidak ada tabel di halaman
            showToast('Mengekspor ' + type + ' sebagai Excel...', 'info');
            window.location.href = 'export_excel.php?table=' + encodeURIComponent(table);
        }
    } else {
        // Format tidak dikenal, arahkan ke Excel sebagai default
        showToast('Mengekspor ' + type + '...', 'info');
        window.location.href = 'export_excel.php?table=' + encodeURIComponent(table);
    }
}

/**
 * Show toast notification
 */
function showToast(message, type) {
    var bgColor = {
        'success': '#28a745',
        'error': '#dc3545',
        'warning': '#ffc107',
        'info': '#17a2b8'
    }[type] || '#17a2b8';
    
    var toast = $('<div class="alert-toast"></div>')
        .css({
            'background': bgColor,
            'color': 'white',
            'padding': '15px 20px',
            'borderRadius': '8px',
            'boxShadow': '0 5px 15px rgba(0,0,0,0.2)',
            'zIndex': 9999,
            'position': 'fixed',
            'bottom': '20px',
            'right': '20px',
            'animation': 'toastSlide 0.5s ease'
        })
        .text(message)
        .appendTo('body');
    
    setTimeout(function() {
        toast.fadeOut(function() {
            $(this).remove();
        });
    }, 3000);
}

/**
 * Check if element is in viewport
 */
function isElementInViewport(el) {
    var rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

/**
 * Initialize modals
 */
function initializeModals() {
    // Add custom styles to modals
    $('.modal').on('show.bs.modal', function() {
        $(this).find('.modal-content').addClass('shadow-custom');
    });
}

/**
 * Initialize charts
 */
function initializeCharts() {
    // Global chart defaults
    Chart.defaults.color = '#495057';
    Chart.defaults.font.family = '"Segoe UI", Tahoma, Geneva, Verdana, sans-serif';
    Chart.defaults.font.size = 12;
}

/**
 * Initialize data tables
 */
function initializeDataTables() {
    // Add custom styles to data tables
    if ($.fn.DataTable) {
        $.extend(true, $.fn.DataTable.defaults, {
            "language": {
                "emptyTable": "Belum ada data yang tersedia",
                "info": "Menampilkan _START_–_END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 data",
                "infoFiltered": "(disaring dari _MAX_ data)",
                "lengthMenu": "Tampilkan _MENU_ data",
                "loadingRecords": "Memuat data...",
                "processing": "Memproses...",
                "search": "Cari:",
                "zeroRecords": "Data yang dicari tidak ditemukan",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Berikutnya",
                    "previous": "Sebelumnya"
                }
            },
            "order": [],
            "pagingType": "simple_numbers",
            "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]]
        });
    }
}

/**
 * Generate report — menggunakan jsPDF UMD (window.jspdf.jsPDF)
 */
function generateReport() {
    var content = document.getElementById('report-content');
    if (!content) {
        showToast('Elemen report-content tidak ditemukan.', 'error');
        return;
    }

    // Pastikan jsPDF sudah ter-load (format UMD: window.jspdf.jsPDF)
    if (typeof window.jspdf === 'undefined' || typeof window.jspdf.jsPDF === 'undefined') {
        showToast('Library jsPDF belum siap. Coba muat ulang halaman.', 'error');
        return;
    }

    showToast('Membuat PDF, mohon tunggu...', 'info');

    html2canvas(content, { scale: 2, useCORS: true }).then(function(canvas) {
        var imgData = canvas.toDataURL('image/png');
        var jsPDF = window.jspdf.jsPDF;
        var doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a4' });

        var pageWidth  = doc.internal.pageSize.getWidth();
        var pageHeight = doc.internal.pageSize.getHeight();
        var margin     = 10;
        var imgWidth   = pageWidth - margin * 2;
        var imgHeight  = (canvas.height * imgWidth) / canvas.width;

        // Jika lebih tinggi dari satu halaman, potong menjadi beberapa halaman
        var yPosition = margin;
        var remainingHeight = imgHeight;

        while (remainingHeight > 0) {
            doc.addImage(imgData, 'PNG', margin, yPosition, imgWidth, imgHeight);
            remainingHeight -= (pageHeight - margin * 2);
            if (remainingHeight > 0) {
                doc.addPage();
                yPosition = margin - (imgHeight - remainingHeight);
            }
        }

        doc.save('laporan-wifi-halow-' + new Date().toISOString().slice(0, 10) + '.pdf');
        showToast('PDF berhasil dibuat!', 'success');
    }).catch(function(err) {
        showToast('Gagal membuat PDF: ' + err.message, 'error');
    });
}

/**
 * Print page
 */
function printPage() {
    window.print();
}

/**
 * Export to CSV
 */
function exportCSV(tableId, filename) {
    var table = $('#' + tableId);
    var csv = [];
    
    // Get headers
    var headers = [];
    table.find('thead th').each(function() {
        headers.push('"' + $(this).text() + '"');
    });
    csv.push(headers.join(','));
    
    // Get rows
    table.find('tbody tr').each(function() {
        var row = [];
        $(this).find('td').each(function() {
            row.push('"' + $(this).text() + '"');
        });
        csv.push(row.join(','));
    });
    
    // Download
    var csvString = csv.join('\n');
    var blob = new Blob([csvString], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = filename || 'data.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

/**
 * AJAX submit form
 */
function ajaxSubmit(formId, callback) {
    var form = $('#' + formId);
    var formData = new FormData(form[0]);
    
    $.ajax({
        url: form.attr('action'),
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (callback) callback(response);
            showToast('Data berhasil disimpan!', 'success');
        },
        error: function(xhr) {
            showToast('Terjadi kesalahan: ' + xhr.statusText, 'error');
        }
    });
}

/**
 * Delete confirmation
 */
function confirmDelete(url, callback) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak dapat dibatalkan.')) {
        $.ajax({
            url: url,
            type: 'DELETE',
            success: function(response) {
                if (callback) callback(response);
                showToast('Data berhasil dihapus!', 'success');
                location.reload();
            },
            error: function(xhr) {
                showToast('Gagal menghapus data: ' + xhr.statusText, 'error');
            }
        });
    }
}

/**
 * Format number
 */
function formatNumber(num) {
    return num.toString().replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
}

/**
 * Format date
 */
function formatDate(dateString) {
    var options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

/**
 * Format time
 */
function formatTime(timeString) {
    var date = new Date(timeString);
    return date.toLocaleTimeString('id-ID');
}

// Add CSS animations
var style = document.createElement('style');
style.textContent = `
    @keyframes toastSlide {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);

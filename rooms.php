<?php
$page_title = 'Rooms & Suites - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Get filter values
$filter_type = $_GET['room_type'] ?? '';
$filter_min_price = $_GET['min_price'] ?? '';
$filter_max_price = $_GET['max_price'] ?? '';
$filter_capacity = $_GET['capacity'] ?? '';
$filter_check_in = $_GET['check_in'] ?? '';
$filter_check_out = $_GET['check_out'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build query
$sql = "SELECT r.*, rt.name AS type_name, rt.description AS type_description, rt.amenities, rt.max_guests, rt.base_price
        FROM rooms r
        JOIN room_types rt ON r.room_type_id = rt.id
        WHERE rt.status = 'active'";
$params = [];

if ($filter_type) {
    $sql .= " AND r.room_type_id = ?";
    $params[] = $filter_type;
}
if ($filter_min_price !== '') {
    $sql .= " AND r.price_per_night >= ?";
    $params[] = (float)$filter_min_price;
}
if ($filter_max_price !== '') {
    $sql .= " AND r.price_per_night <= ?";
    $params[] = (float)$filter_max_price;
}
if ($filter_capacity !== '') {
    $sql .= " AND rt.max_guests >= ?";
    $params[] = (int)$filter_capacity;
}
if ($filter_status) {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
}

// Exclude rooms booked in date range
if ($filter_check_in && $filter_check_out) {
    $sql .= " AND r.id NOT IN (
        SELECT room_id FROM bookings
        WHERE booking_status NOT IN ('cancelled', 'checked_out')
        AND (check_in_date < ? AND check_out_date > ?)
    )";
    $params[] = $filter_check_out;
    $params[] = $filter_check_in;
}

$sql .= " ORDER BY r.price_per_night ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rooms = $stmt->fetchAll();

// Fetch room types for filter
$room_types = getRoomTypes();

// Fetch distinct statuses
$statuses = ['available', 'reserved', 'occupied', 'cleaning', 'maintenance', 'out_of_service'];
?>
<!-- Page Hero -->
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Rooms &amp; Suites</span>
        </nav>
        <h1 class="page-hero-title">Rooms &amp; Suites</h1>
        <p class="hero-subtitle">Discover your perfect accommodation</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row">
            <!-- Filter Sidebar -->
            <div class="col-lg-3">
                <div class="filter-sidebar">
                    <h3 class="filter-title"><i class="bi bi-funnel me-2" style="color:var(--gold);"></i>Filter Rooms</h3>
                    <form action="" method="GET" id="filterForm">
                        <div class="filter-group">
                            <label class="filter-label">Room Type</label>
                            <select class="form-select" name="room_type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <?php foreach ($room_types as $rt): ?>
                                <option value="<?php echo $rt['id']; ?>" <?php echo $filter_type == $rt['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($rt['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Min Price</label>
                            <input type="number" class="form-control" name="min_price" placeholder="Min" value="<?php echo htmlspecialchars($filter_min_price); ?>" onchange="this.form.submit()">
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Max Price</label>
                            <input type="number" class="form-control" name="max_price" placeholder="Max" value="<?php echo htmlspecialchars($filter_max_price); ?>" onchange="this.form.submit()">
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Capacity (Guests)</label>
                            <select class="form-select" name="capacity" onchange="this.form.submit()">
                                <option value="">Any</option>
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filter_capacity == $i ? 'selected' : ''; ?>>
                                    <?php echo $i; ?> <?php echo $i === 1 ? 'Guest' : 'Guests'; ?>
                                </option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Status</label>
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $filter_status === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Check-In</label>
                            <input type="date" class="form-control" name="check_in" value="<?php echo htmlspecialchars($filter_check_in); ?>" min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="filter-group">
                            <label class="filter-label">Check-Out</label>
                            <input type="date" class="form-control" name="check_out" value="<?php echo htmlspecialchars($filter_check_out); ?>">
                        </div>

                        <?php if ($filter_type || $filter_min_price || $filter_max_price || $filter_capacity || $filter_status || ($filter_check_in && $filter_check_out)): ?>
                        <a href="<?php echo BASE_URL; ?>rooms.php" class="btn btn-outline-gold w-100 mt-2">
                            <i class="bi bi-x-circle me-2"></i>Clear Filters
                        </a>
                        <?php endif; ?>

                        <button type="submit" class="btn btn-gold w-100 mt-3">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                    </form>
                </div>
            </div>

            <!-- Room Grid -->
            <div class="col-lg-9">
                <?php if (!empty($filter_check_in) || !empty($filter_check_out)): ?>
                <div class="alert alert-info mb-4" style="border-radius: var(--radius-md);">
                    <i class="bi bi-info-circle me-2"></i>
                    Showing rooms available from <strong><?php echo htmlspecialchars($filter_check_in ?: 'any'); ?></strong> to <strong><?php echo htmlspecialchars($filter_check_out ?: 'any'); ?></strong>.
                    <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Room Availability Calendar -->
                <div class="cal-container" id="roomCalendar">
                    <div class="cal-header">
                        <div class="cal-header-left">
                            <div class="cal-icon"><i class="bi bi-calendar3"></i></div>
                            <div>
                                <h3 class="cal-title">Room Availability</h3>
                                <p class="cal-subtitle">Real-time room status across all categories</p>
                            </div>
                        </div>
                        <div class="cal-nav">
                            <button type="button" class="cal-nav-btn" id="calPrev"><i class="bi bi-chevron-left"></i></button>
                            <div class="cal-month-display">
                                <span id="calMonthLabel" class="cal-month-text"></span>
                                <button type="button" class="cal-today-btn" id="calToday">Today</button>
                            </div>
                            <button type="button" class="cal-nav-btn" id="calNext"><i class="bi bi-chevron-right"></i></button>
                        </div>
                    </div>

                    <div class="cal-stats" id="calStats">
                        <div class="cal-stat">
                            <span class="cal-stat-dot" style="background:var(--success);"></span>
                            <span class="cal-stat-label">Available</span>
                            <span class="cal-stat-count" id="statAvailable">-</span>
                        </div>
                        <div class="cal-stat">
                            <span class="cal-stat-dot" style="background:var(--warning);"></span>
                            <span class="cal-stat-label">Reserved</span>
                            <span class="cal-stat-count" id="statReserved">-</span>
                        </div>
                        <div class="cal-stat">
                            <span class="cal-stat-dot" style="background:var(--danger);"></span>
                            <span class="cal-stat-label">Occupied</span>
                            <span class="cal-stat-count" id="statOccupied">-</span>
                        </div>
                        <div class="cal-stat">
                            <span class="cal-stat-dot" style="background:var(--mid-gray, #6b7280);"></span>
                            <span class="cal-stat-label">Unavailable</span>
                            <span class="cal-stat-count" id="statUnavailable">-</span>
                        </div>
                        <div class="cal-stat cal-stat-total">
                            <span class="cal-stat-label">Total Rooms</span>
                            <span class="cal-stat-count" id="statTotal">-</span>
                        </div>
                    </div>

                    <div class="cal-table-wrap">
                        <table class="cal-table" id="calTable">
                            <thead>
                                <tr id="calHeader"></tr>
                            </thead>
                            <tbody id="calBody">
                                <tr><td colspan="32" class="cal-loading"><div class="cal-spinner"></div><span>Loading availability...</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (!empty($rooms)): ?>
                <?php $room_photos = ['1631049307264-da0ec9d70304', '1596394516093-501ba68a0ba6', '1611892440504-42a792e24d32', '1578683010236-d716f9a3f461', '1582719508461-905c673771fd', '1566665797739-1674de7a421a', '1551882547-ff40c63fe5fa', '1618773928121-c32242e63f39']; ?>
                <div class="listing-grid">
                    <?php foreach ($rooms as $room): ?>
                    <div class="listing-card">
                        <div class="listing-image">
                            <?php if ($room['image'] && (strpos($room['image'], 'http') === 0 || file_exists(__DIR__ . '/assets/images/rooms/' . $room['image']))): ?>
                            <img src="<?php echo strpos($room['image'], 'http') === 0 ? $room['image'] : BASE_URL . 'assets/images/rooms/' . htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>" loading="lazy">
                            <?php else: ?>
                            <img src="https://images.unsplash.com/photo-<?php echo $room_photos[$room['id'] % count($room_photos)]; ?>?w=400&h=280&fit=crop" alt="<?php echo htmlspecialchars($room['type_name']); ?>" loading="lazy">
                            <?php endif; ?>
                            <button class="listing-wishlist" aria-label="Save to wishlist"><i class="bi bi-heart"></i></button>
                            <div class="listing-status" style="background: <?php echo $room['status'] === 'available' ? 'rgba(16,185,129,0.85)' : ($room['status'] === 'occupied' ? 'rgba(239,68,68,0.85)' : 'rgba(245,158,11,0.85)'); ?>"><?php echo htmlspecialchars(ucfirst($room['status'])); ?></div>
                        </div>
                        <div class="listing-body">
                            <div class="listing-type"><?php echo htmlspecialchars($room['type_name']); ?></div>
                            <h3 class="listing-title"><?php echo htmlspecialchars($room['room_number']); ?></h3>
                            <p class="listing-desc"><?php echo htmlspecialchars(truncate($room['description'] ?? $room['type_description'] ?? 'Experience luxury at its finest.', 60)); ?></p>
                            <div class="listing-features">
                                <span><i class="bi bi-people"></i> <?php echo $room['max_guests']; ?> guests</span>
                                <?php $amenities_list = array_slice(explode(',', $room['amenities'] ?? ''), 0, 2);
                                foreach ($amenities_list as $amenity): ?>
                                <span><i class="bi bi-check-lg"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                                <?php endforeach; ?>
                            </div>
                            <div class="listing-footer">
                                <div class="listing-price">
                                    <?php echo formatMoney($room['price_per_night']); ?>
                                    <span>/night</span>
                                </div>
                                <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $room['id']; ?>&branch_id=<?php echo $room['branch_id']; ?>&room_type_id=<?php echo $room['room_type_id']; ?>" class="listing-book">Book Now</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-building-slash" style="font-size: 4rem; color: var(--mid-gray); display: block; margin-bottom: 16px;"></i>
                    <h3 style="font-family: var(--font-serif); color: var(--charcoal);">No Rooms Found</h3>
                    <p style="color: var(--mid-gray);">Try adjusting your filters or search criteria.</p>
                    <a href="<?php echo BASE_URL; ?>rooms.php" class="btn btn-outline-gold mt-3">
                        <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Sync dates
(function() {
    var checkIn = document.querySelector('input[name="check_in"]');
    var checkOut = document.querySelector('input[name="check_out"]');
    if (checkIn && checkOut) {
        checkIn.addEventListener('change', function() {
            var minOut = new Date(this.value);
            minOut.setDate(minOut.getDate() + 1);
            checkOut.min = minOut.toISOString().split('T')[0];
            if (checkOut.value && checkOut.value <= this.value) {
                checkOut.value = '';
            }
        });
    }
})();

// Room Availability Calendar
(function() {
    var now = new Date();
    var calYear = now.getFullYear();
    var calMonth = now.getMonth() + 1;
    var header = document.getElementById('calHeader');
    var body = document.getElementById('calBody');
    var label = document.getElementById('calMonthLabel');

    var statusConfig = {
        available:   { color: '#10b981', bg: 'rgba(16,185,129,0.08)', label: 'Available' },
        reserved:    { color: '#f59e0b', bg: 'rgba(245,158,11,0.08)', label: 'Reserved' },
        occupied:    { color: '#ef4444', bg: 'rgba(239,68,68,0.08)',  label: 'Occupied' },
        unavailable: { color: '#6b7280', bg: 'rgba(107,114,128,0.08)', label: 'Unavailable' },
        cleaning:    { color: '#8b5cf6', bg: 'rgba(139,92,246,0.08)', label: 'Cleaning' },
        past:        { color: '#d1d5db', bg: 'rgba(0,0,0,0.02)',      label: 'Past' }
    };

    var typeColors = {
        'Deluxe Room': '#10b981',
        'Executive Room': '#3b82f6',
        'Luxury Suite': '#8b5cf6',
        'Presidential Suite': '#f59e0b',
        'Penthouse': '#ef4444'
    };

    function getCookie(name) {
        var v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
        return v ? v.pop() : null;
    }

    function loadCalendar() {
        label.textContent = calYear + '...';
        body.innerHTML = '<tr><td colspan="32" class="cal-loading"><div class="cal-spinner"></div><span>Loading availability...</span></td></tr>';

        fetch('/ajax/room-calendar.php?year=' + calYear + '&month=' + calMonth)

            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    body.innerHTML = '<tr><td colspan="32" class="cal-empty"><i class="bi bi-exclamation-triangle" style="color:var(--danger);font-size:1.5rem;"></i><span>Failed to load calendar data</span></td></tr>';
                    return;
                }

                label.textContent = data.month_name + ' ' + data.year;

                var hdr = '<th class="cal-th-room">Room</th>';
                var today = new Date();
                var isCurrentMonth = (today.getFullYear() === data.year && today.getMonth() + 1 === data.month);
                var dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

                for (var d = 1; d <= data.days; d++) {
                    var dayDate = new Date(data.year, data.month - 1, d);
                    var isToday = isCurrentMonth && d === today.getDate();
                    var isWeekend = dayDate.getDay() === 0 || dayDate.getDay() === 6;
                    var cls = 'cal-th-day';
                    if (isToday) cls += ' cal-today-col';
                    if (isWeekend) cls += ' cal-weekend-col';
                    hdr += '<th class="' + cls + '"><span class="cal-day-abbr">' + dayNames[dayDate.getDay()] + '</span><span class="cal-day-num">' + d + '</span></th>';
                }
                header.innerHTML = hdr;

                var groups = {};
                data.rooms.forEach(function(room) {
                    var tn = room.type_name || 'Other';
                    if (!groups[tn]) groups[tn] = [];
                    groups[tn].push(room);
                });

                var rows = '';
                var typeOrder = ['Deluxe Room', 'Executive Room', 'Luxury Suite', 'Presidential Suite', 'Penthouse'];
                typeOrder.forEach(function(tn) {
                    if (!groups[tn]) return;
                    var rooms = groups[tn];
                    var tc = typeColors[tn] || '#6b7280';
                    rows += '<tr class="cal-type-row"><td class="cal-type-cell" colspan="' + (data.days + 1) + '"><span class="cal-type-badge" style="--tc:' + tc + ';">' + tn + '</span><span class="cal-type-count">' + rooms.length + ' room' + (rooms.length > 1 ? 's' : '') + '</span></td></tr>';

                    rooms.forEach(function(room) {
                        rows += '<tr class="cal-room-row">';
                        rows += '<td class="cal-td-room"><span class="cal-room-num">' + room.room_number + '</span></td>';

                        for (var d = 1; d <= data.days; d++) {
                            var ds = room.days[d];
                            var sc = statusConfig[ds] || statusConfig.past;
                            var dayDate2 = new Date(data.year, data.month - 1, d);
                            var isToday = isCurrentMonth && d === today.getDate();
                            var isWeekend = dayDate2.getDay() === 0 || dayDate2.getDay() === 6;
                            var cls = 'cal-td-day';
                            if (isToday) cls += ' cal-today-col';
                            if (isWeekend) cls += ' cal-weekend-col';
                            if (isCurrentMonth && d < today.getDate() && ds === 'available') cls += ' cal-past-day';

                            var dateStr = data.year + '-' + String(data.month).padStart(2, '0') + '-' + String(d).padStart(2, '0');
                            var title = room.room_number + ' \u2014 ' + sc.label + '\n' + dateStr + '\u2014' + room.type_name + ' \u2014 \u20A6' + Number(room.price).toLocaleString();
                            var link = (ds === 'available' && !isCurrentMonth || (isCurrentMonth && d >= today.getDate())) ? '/booking.php?room_id=' + room.id + '&check_in=' + dateStr : '';

                            rows += '<td class="' + cls + '" title="' + title.replace(/"/g, '&quot;') + '">';
                            rows += '<span class="cal-pill" style="--pc:' + sc.color + ';--pb:' + sc.bg + ';">';
                            if (link) rows += '<a href="' + link + '" class="cal-pill-link"></a>';
                            rows += '</span>';
                            rows += '</td>';
                        }
                        rows += '</tr>';
                    });
                });

                if (!rows) {
                    rows = '<tr><td colspan="32" class="cal-empty"><i class="bi bi-calendar-x" style="font-size:1.5rem;color:var(--text-light);"></i><span>No rooms found</span></td></tr>';
                }
                body.innerHTML = rows;

                var s = data.summary || {};
                document.getElementById('statAvailable').textContent = s.available || 0;
                document.getElementById('statReserved').textContent = s.reserved || 0;
                document.getElementById('statOccupied').textContent = s.occupied || 0;
                document.getElementById('statUnavailable').textContent = s.unavailable || 0;
                var total = (s.available || 0) + (s.reserved || 0) + (s.occupied || 0) + (s.unavailable || 0);
                document.getElementById('statTotal').textContent = total;
            })
            .catch(function() {
                body.innerHTML = '<tr><td colspan="32" class="cal-empty"><i class="bi bi-wifi-off" style="color:var(--danger);font-size:1.5rem;"></i><span>Connection error</span></td></tr>';
            });
    }

    document.getElementById('calPrev').addEventListener('click', function() {
        calMonth--;
        if (calMonth < 1) { calMonth = 12; calYear--; }
        loadCalendar();
    });

    document.getElementById('calNext').addEventListener('click', function() {
        calMonth++;
        if (calMonth > 12) { calMonth = 1; calYear++; }
        loadCalendar();
    });

    document.getElementById('calToday').addEventListener('click', function() {
        var n = new Date();
        calYear = n.getFullYear();
        calMonth = n.getMonth() + 1;
        loadCalendar();
    });

    loadCalendar();
})();
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>

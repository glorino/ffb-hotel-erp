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

                <?php if (!empty($rooms)): ?>
                <div class="row g-4">
                    <?php foreach ($rooms as $room): ?>
                    <div class="col-md-6">
                        <div class="room-card">
                            <div class="room-card-image">
                                <?php if ($room['image']): ?>
                                <img src="<?php echo BASE_URL; ?>assets/images/rooms/<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['type_name']); ?>" class="room-img">
                                <?php else: ?>
                                <div class="room-img" style="background: linear-gradient(135deg, #1a1a2e, #16213e); display:flex; align-items:center; justify-content:center; color:var(--gold); font-size:3rem;">
                                    <i class="bi bi-building"></i>
                                </div>
                                <?php endif; ?>
                                <div class="room-status-badge"><?php echo getRoomStatusBadge($room['status']); ?></div>
                                <div class="room-type-badge"><?php echo htmlspecialchars($room['type_name']); ?></div>
                                <div class="room-price-badge"><?php echo formatMoney($room['price_per_night']); ?> <small>/ night</small></div>
                            </div>
                            <div class="room-card-body">
                                <h3 class="room-type"><?php echo htmlspecialchars($room['type_name']); ?> - Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                                <p class="room-desc"><?php echo htmlspecialchars(truncate($room['description'] ?? $room['type_description'] ?? 'Beautifully appointed room designed for your ultimate comfort and relaxation.', 150)); ?></p>
                                <div class="room-amenities">
                                    <span class="amenity-tag"><i class="bi bi-people"></i> <?php echo $room['max_guests']; ?> Guests</span>
                                    <span class="amenity-tag"><i class="bi bi-layers"></i> Floor <?php echo htmlspecialchars($room['floor'] ?? 'N/A'); ?></span>
                                    <?php
                                    $amenities_list = explode(',', $room['amenities'] ?? '');
                                    $amenities_list = array_slice($amenities_list, 0, 3);
                                    foreach ($amenities_list as $amenity):
                                    ?>
                                    <span class="amenity-tag"><i class="bi bi-check-circle"></i> <?php echo htmlspecialchars(trim($amenity)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="room-card-footer">
                                <a href="<?php echo BASE_URL; ?>booking.php?room_id=<?php echo $room['id']; ?>" class="btn btn-gold w-100">
                                    <i class="bi bi-calendar-check me-2"></i>Book Now
                                </a>
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
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>

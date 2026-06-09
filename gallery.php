<?php
$page_title = 'Gallery - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

$stmt = $db->prepare("SELECT * FROM gallery_items WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$gallery_items = $stmt->fetchAll();

$stmt_cat = $db->prepare("SELECT DISTINCT category FROM gallery_items WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category");
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="page-hero">
    <div class="page-hero-bg"></div>
    <div class="page-hero-overlay"></div>
    <div class="page-hero-content">
        <nav class="page-hero-breadcrumb mb-3">
            <a href="<?php echo BASE_URL; ?>index.php">Home</a>
            <span>/</span>
            <span>Gallery</span>
        </nav>
        <h1 class="page-hero-title">Our Gallery</h1>
        <p class="hero-subtitle">A visual journey through elegance and luxury</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="gallery-filters" id="galleryFilters">
            <button class="filter-btn active" data-filter="all"><i class="bi bi-grid-3x3-gap me-1"></i> All</button>
            <button class="filter-btn" data-filter="rooms"><i class="bi bi-door-open me-1"></i> Rooms</button>
            <button class="filter-btn" data-filter="suites"><i class="bi bi-star me-1"></i> Suites</button>
            <button class="filter-btn" data-filter="restaurant"><i class="bi bi-cup-hot me-1"></i> Restaurant</button>
            <button class="filter-btn" data-filter="lobby"><i class="bi bi-building me-1"></i> Lobby</button>
            <button class="filter-btn" data-filter="spa"><i class="bi bi-water me-1"></i> Spa</button>
            <button class="filter-btn" data-filter="pool"><i class="bi bi-water me-1"></i> Pool</button>
            <button class="filter-btn" data-filter="exterior"><i class="bi bi-house-door me-1"></i> Exterior</button>
            <button class="filter-btn" data-filter="events"><i class="bi bi-calendar-event me-1"></i> Events</button>
        </div>

        <?php
        $all_photos = [
            // Rooms
            ['src' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Deluxe Room'],
            ['src' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Executive Room'],
            ['src' => 'https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Penthouse'],
            ['src' => 'https://images.unsplash.com/photo-1566665797739-1674de7a421a?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Room Interior'],
            ['src' => 'https://images.unsplash.com/photo-1596394516093-501ba68a0ba6?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'City View Room'],
            ['src' => 'https://images.unsplash.com/photo-1631049054344-47b4d9fafde4?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Standard Room'],
            ['src' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Twin Room'],
            ['src' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Room with Balcony'],
            ['src' => 'https://images.unsplash.com/photo-1585412727339-54e4bae3bbf9?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Modern Room'],
            ['src' => 'https://images.unsplash.com/photo-1617325247661-675ab4b64ae2?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Cozy Room'],
            ['src' => 'https://images.unsplash.com/photo-1590490360182-c33d7ef4ec95?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Premium Room'],
            ['src' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&h=560&fit=crop', 'cat' => 'rooms', 'title' => 'Garden View Room'],

            // Suites
            ['src' => 'https://images.unsplash.com/photo-1578683010236-d716f9a3f461?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Luxury Suite'],
            ['src' => 'https://images.unsplash.com/photo-1590490362-c33d57733427?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Presidential Suite'],
            ['src' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Suite Living Area'],
            ['src' => 'https://images.unsplash.com/photo-1579684947550-22e945225d4a?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Penthouse Suite'],
            ['src' => 'https://images.unsplash.com/photo-1584132915807-fd1f5fbc078f?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Suite Bedroom'],
            ['src' => 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Suite Dining Area'],
            ['src' => 'https://images.unsplash.com/photo-1602002418082-a4443e081dd1?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Suite Bathroom'],
            ['src' => 'https://images.unsplash.com/photo-1595526114035-0d45ed16cfbf?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Executive Suite'],
            ['src' => 'https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=800&h=560&fit=crop', 'cat' => 'suites', 'title' => 'Suite Terrace'],

            // Restaurant
            ['src' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Fine Dining Restaurant'],
            ['src' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Gourmet Cuisine'],
            ['src' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Bar & Lounge'],
            ['src' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Outdoor Terrace'],
            ['src' => 'https://images.unsplash.com/photo-1550966871-3ed3cdb51f3a?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Breakfast Buffet'],
            ['src' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Cocktail Bar'],
            ['src' => 'https://images.unsplash.com/photo-1600891964092-4316c288032e?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Grilled Steak'],
            ['src' => 'https://images.unsplash.com/photo-1567620905732-2d1ec7ab7445?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Brunch Spread'],
            ['src' => 'https://images.unsplash.com/photo-1476224203421-9ac39bcb3327?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Pasta Dish'],
            ['src' => 'https://images.unsplash.com/photo-1551024506-0bccd828d307?w=800&h=560&fit=crop', 'cat' => 'restaurant', 'title' => 'Dessert Platter'],

            // Lobby
            ['src' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Grand Lobby'],
            ['src' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Reception Desk'],
            ['src' => 'https://images.unsplash.com/photo-1584132967334-10e028bd69f7?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Lounge Area'],
            ['src' => 'https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Chandelier Hall'],
            ['src' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Elegant Foyer'],
            ['src' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Hotel Atrium'],
            ['src' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Grand Entrance'],
            ['src' => 'https://images.unsplash.com/photo-1590381105924-c72589b1ef3f?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Staircase'],
            ['src' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=800&h=560&fit=crop', 'cat' => 'lobby', 'title' => 'Seating Area'],

            // Spa
            ['src' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Spa & Wellness'],
            ['src' => 'https://images.unsplash.com/photo-1540555700478-4be289fbec6d?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Massage Treatment'],
            ['src' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Sauna'],
            ['src' => 'https://images.unsplash.com/photo-1552321554-5fefe8c9ef14?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Relaxation Area'],
            ['src' => 'https://images.unsplash.com/photo-1544367567-0f2fcb009e0b?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Yoga Studio'],
            ['src' => 'https://images.unsplash.com/photo-1507652313519-d4e9174996dd?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Steam Room'],
            ['src' => 'https://images.unsplash.com/photo-1515377905703-c4788e51af15?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Hot Stone Therapy'],
            ['src' => 'https://images.unsplash.com/photo-1540497077202-7c8a3999166f?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Aromatherapy'],
            ['src' => 'https://images.unsplash.com/photo-1596178065887-1198b6148b2b?w=800&h=560&fit=crop', 'cat' => 'spa', 'title' => 'Facial Treatment'],

            // Pool
            ['src' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Infinity Pool'],
            ['src' => 'https://images.unsplash.com/photo-1572331165267-854da2b021b1?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Poolside Lounge'],
            ['src' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Pool Bar'],
            ['src' => 'https://images.unsplash.com/photo-1563720223185-11003d516935?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Evening Pool'],
            ['src' => 'https://images.unsplash.com/photo-1575429198097-0414ec08e8cd?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Kids Pool'],
            ['src' => 'https://images.unsplash.com/photo-1519449556851-5720b33024e7?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Pool at Sunset'],
            ['src' => 'https://images.unsplash.com/photo-1526976668912-1a885a784f3f?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Cabana Area'],
            ['src' => 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&h=560&fit=crop', 'cat' => 'pool', 'title' => 'Pool & Skyline'],

            // Exterior
            ['src' => 'https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Hotel Exterior'],
            ['src' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Night View'],
            ['src' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Garden View'],
            ['src' => 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Hotel Entrance'],
            ['src' => 'https://images.unsplash.com/photo-1564501049412-61c2a3083791?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Palm Drive'],
            ['src' => 'https://images.unsplash.com/photo-1455587734955-081b22074882?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Rooftop Terrace'],
            ['src' => 'https://images.unsplash.com/photo-1519449556851-5720b33024e7?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Aerial View'],
            ['src' => 'https://images.unsplash.com/photo-1571003123894-1f0594d2b5d9?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Hotel Facade'],
            ['src' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=800&h=560&fit=crop', 'cat' => 'exterior', 'title' => 'Evening Ambiance'],

            // Events
            ['src' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Event Hall'],
            ['src' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Wedding Reception'],
            ['src' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Gala Dinner'],
            ['src' => 'https://images.unsplash.com/photo-1478147427282-58a87a120781?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Conference Room'],
            ['src' => 'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Banquet Setup'],
            ['src' => 'https://images.unsplash.com/photo-1505236858219-8359eb29e329?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Pool Party'],
            ['src' => 'https://images.unsplash.com/photo-1530103862676-de8c9debad1d?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Celebration Setup'],
            ['src' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Cocktail Event'],
            ['src' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=800&h=560&fit=crop', 'cat' => 'events', 'title' => 'Corporate Event'],
        ];
        ?>
        <div class="gallery-grid" id="galleryGrid">
            <?php
            $db_images = [];
            if (!empty($gallery_items)) {
                foreach ($gallery_items as $item) {
                    $img_path = __DIR__ . '/assets/images/gallery/' . ($item['image'] ?? '');
                    if (!empty($item['image']) && file_exists($img_path)) {
                        $db_images[] = $item;
                    }
                }
            }
            ?>
            <?php foreach ($db_images as $item): ?>
            <div class="gallery-item" data-category="<?php echo htmlspecialchars($item['category'] ?? 'other'); ?>">
                <img src="<?php echo BASE_URL; ?>assets/images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" loading="lazy" referrerpolicy="no-referrer" crossorigin="anonymous">
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category"><?php echo htmlspecialchars(ucfirst($item['category'] ?? 'Hotel')); ?></span>
                        <h4 class="gallery-title"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php foreach ($all_photos as $g): ?>
            <div class="gallery-item" data-category="<?php echo $g['cat']; ?>">
                <img src="<?php echo $g['src']; ?>" alt="<?php echo $g['title']; ?>" loading="lazy" referrerpolicy="no-referrer" crossorigin="anonymous">
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category"><?php echo ucfirst($g['cat']); ?></span>
                        <h4 class="gallery-title"><?php echo $g['title']; ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <img class="lightbox-img" id="lightboxImg" src="" alt="">
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<script>
(function() {
    var filterBtns = document.querySelectorAll('.filter-btn');
    var items = document.querySelectorAll('.gallery-item');

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            var filter = this.getAttribute('data-filter');
            filterBtns.forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            items.forEach(function(item) {
                if (filter === 'all' || item.getAttribute('data-category') === filter) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    document.addEventListener('click', function(e) {
        var target = e.target.closest('.gallery-view, .gallery-overlay');
        if (target) {
            var item = target.closest('.gallery-item');
            if (item) {
                var img = item.querySelector('img');
                var cap = item.querySelector('.gallery-title');
                if (img) openLightbox(img.src, cap ? cap.textContent : '');
            }
        }
    });

    function openLightbox(src, caption) {
        var overlay = document.getElementById('lightboxOverlay');
        var img = document.getElementById('lightboxImg');
        var cap = document.getElementById('lightboxCaption');
        if (overlay && img) {
            img.src = src;
            cap.textContent = caption || '';
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    window.closeLightbox = function() {
        var overlay = document.getElementById('lightboxOverlay');
        if (overlay) {
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeLightbox();
    });
})();
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>

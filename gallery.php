<?php
$page_title = 'Gallery - FFB Hotel';
require_once __DIR__ . '/includes/public-header.php';

$db = getDB();

// Fetch gallery items from DB
$stmt = $db->prepare("SELECT * FROM gallery_items WHERE status = 'active' ORDER BY created_at DESC");
$stmt->execute();
$gallery_items = $stmt->fetchAll();

// Get unique categories
$stmt_cat = $db->prepare("SELECT DISTINCT category FROM gallery_items WHERE status = 'active' AND category IS NOT NULL AND category != '' ORDER BY category");
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll(PDO::FETCH_COLUMN);
?>
<!-- Page Hero -->
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
        <!-- Filter Buttons -->
        <div class="gallery-filters">
            <button class="filter-btn active" data-filter="all">All</button>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                <button class="filter-btn" data-filter="<?php echo htmlspecialchars($cat); ?>">
                    <?php echo htmlspecialchars(ucfirst($cat)); ?>
                </button>
                <?php endforeach; ?>
            <?php else: ?>
                <button class="filter-btn" data-filter="rooms">Rooms</button>
                <button class="filter-btn" data-filter="restaurant">Restaurant</button>
                <button class="filter-btn" data-filter="lobby">Lobby</button>
                <button class="filter-btn" data-filter="exterior">Exterior</button>
                <button class="filter-btn" data-filter="events">Events</button>
            <?php endif; ?>
        </div>

        <?php $gallery_photos = [
            'lobby' => 'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=600&h=400&fit=crop',
            'rooms' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=600&h=400&fit=crop',
            'restaurant' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=600&h=400&fit=crop',
            'exterior' => 'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600&h=400&fit=crop',
            'events' => 'https://images.unsplash.com/photo-1511578314322-379afb476865?w=600&h=400&fit=crop',
            'spa' => 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?w=600&h=400&fit=crop',
            'other' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?w=600&h=400&fit=crop',
        ]; ?>
        <div class="gallery-grid" id="galleryGrid">
            <?php if (!empty($gallery_items)): ?>
            <?php foreach ($gallery_items as $item): ?>
            <div class="gallery-item" data-category="<?php echo htmlspecialchars($item['category'] ?? 'other'); ?>">
                <?php if ($item['image'] && file_exists(__DIR__ . '/assets/images/gallery/' . $item['image'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>" loading="lazy">
                <?php else: ?>
                <img src="<?php echo $gallery_photos[$item['category'] ?? 'other'] ?? $gallery_photos['other']; ?>" alt="<?php echo htmlspecialchars($item['title'] ?? 'Gallery'); ?>" loading="lazy">
                <?php endif; ?>
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category"><?php echo htmlspecialchars(ucfirst($item['category'] ?? 'Hotel')); ?></span>
                        <h4 class="gallery-title"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php else: ?>
            <?php $gallery_fb = [
                ['src' => $gallery_photos['lobby'], 'cat' => 'lobby', 'title' => 'Luxury Lobby'],
                ['src' => $gallery_photos['rooms'], 'cat' => 'rooms', 'title' => 'Deluxe Suite'],
                ['src' => $gallery_photos['restaurant'], 'cat' => 'restaurant', 'title' => 'Fine Dining'],
                ['src' => $gallery_photos['exterior'], 'cat' => 'exterior', 'title' => 'Infinity Pool'],
                ['src' => $gallery_photos['events'], 'cat' => 'events', 'title' => 'Event Hall'],
                ['src' => $gallery_photos['spa'], 'cat' => 'spa', 'title' => 'Spa &amp; Wellness'],
                ['src' => $gallery_photos['other'], 'cat' => 'other', 'title' => 'Executive Lounge'],
                ['src' => 'https://images.unsplash.com/photo-1563911302283-d2bc129e7570?w=600&h=400&fit=crop', 'cat' => 'rooms', 'title' => 'Presidential Suite'],
            ]; ?>
            <?php foreach ($gallery_fb as $g): ?>
            <div class="gallery-item" data-category="<?php echo $g['cat']; ?>">
                <img src="<?php echo $g['src']; ?>" alt="<?php echo $g['title']; ?>" loading="lazy">
                <div class="gallery-overlay">
                    <div>
                        <span class="gallery-category"><?php echo ucfirst($g['cat']); ?></span>
                        <h4 class="gallery-title"><?php echo $g['title']; ?></h4>
                    </div>
                    <div class="gallery-view"><i class="bi bi-arrows-angle-expand"></i></div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lightboxOverlay" onclick="closeLightbox()">
    <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
    <img class="lightbox-img" id="lightboxImg" src="" alt="">
    <div class="lightbox-caption" id="lightboxCaption"></div>
</div>

<script>
// Gallery Filtering
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
})();

// Gallery view click -> lightbox
document.addEventListener('click', function(e) {
    var view = e.target.closest('.gallery-view');
    if (view) {
        var item = view.closest('.gallery-item');
        if (item) {
            var img = item.querySelector('img');
            var capTxt = item.querySelector('.gallery-title');
            if (img) openLightbox(img.src, capTxt ? capTxt.textContent : '');
        }
    }
    var overlay = e.target.closest('.gallery-overlay');
    if (overlay) {
        var item = overlay.closest('.gallery-item');
        if (item) {
            var img = item.querySelector('img');
            var capTxt = item.querySelector('.gallery-title');
            if (img) openLightbox(img.src, capTxt ? capTxt.textContent : '');
        }
    }
});

// Lightbox
function openLightbox(src, caption) {
    var overlay = document.getElementById('lightboxOverlay');
    var img = document.getElementById('lightboxImg');
    var cap = document.getElementById('lightboxCaption');
    if (overlay && img) {
        if (src) {
            img.src = src;
        } else {
            img.style.display = 'none';
        }
        if (cap) cap.textContent = caption || '';
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeLightbox() {
    var overlay = document.getElementById('lightboxOverlay');
    if (overlay) {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
        var img = document.getElementById('lightboxImg');
        if (img) img.style.display = '';
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php require_once __DIR__ . '/includes/public-footer.php'; ?>

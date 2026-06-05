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

        <?php if (!empty($gallery_items)): ?>
        <!-- Masonry Grid from DB -->
        <div class="gallery-masonry" id="galleryGrid">
            <?php foreach ($gallery_items as $item): ?>
            <div class="masonry-item" data-category="<?php echo htmlspecialchars($item['category'] ?? 'other'); ?>">
                <?php if (file_exists(__DIR__ . '/assets/images/gallery/' . $item['image'])): ?>
                <img src="<?php echo BASE_URL; ?>assets/images/gallery/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title'] ?? ''); ?>">
                <?php else: ?>
                <div style="width:100%;height:250px;background:linear-gradient(135deg,#1a1a2e,#16213e);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;">
                    <i class="bi bi-image"></i>
                </div>
                <?php endif; ?>
                <div class="masonry-overlay" onclick="openLightbox('<?php echo BASE_URL; ?>assets/images/gallery/<?php echo htmlspecialchars($item['image']); ?>', '<?php echo htmlspecialchars($item['title'] ?? ''); ?>')">
                    <div>
                        <span class="masonry-category"><?php echo htmlspecialchars(ucfirst($item['category'] ?? '')); ?></span>
                        <h4 class="masonry-title"><?php echo htmlspecialchars($item['title'] ?? ''); ?></h4>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- Static Masonry Grid -->
        <div class="gallery-masonry" id="galleryGrid">
            <div class="masonry-item" data-category="lobby">
                <div style="width:100%;height:350px;background:linear-gradient(135deg,#1a1a2e,#16213e);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;"><i class="bi bi-building"></i></div>
                <div class="masonry-overlay" onclick="openLightbox('', 'Luxury Lobby')">
                    <div><span class="masonry-category">Lobby</span><h4 class="masonry-title">Luxury Lobby</h4></div>
                </div>
            </div>
            <div class="masonry-item" data-category="rooms">
                <div style="width:100%;height:250px;background:linear-gradient(135deg,#16213e,#1a1a2e);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;"><i class="bi bi-door-open"></i></div>
                <div class="masonry-overlay" onclick="openLightbox('', 'Deluxe Room')">
                    <div><span class="masonry-category">Rooms</span><h4 class="masonry-title">Deluxe Room</h4></div>
                </div>
            </div>
            <div class="masonry-item" data-category="rooms">
                <div style="width:100%;height:300px;background:linear-gradient(135deg,#1a1a2e,#0a0a1a);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;"><i class="bi bi-house-heart"></i></div>
                <div class="masonry-overlay" onclick="openLightbox('', 'Presidential Suite')">
                    <div><span class="masonry-category">Rooms</span><h4 class="masonry-title">Presidential Suite</h4></div>
                </div>
            </div>
            <div class="masonry-item" data-category="restaurant">
                <div style="width:100%;height:280px;background:linear-gradient(135deg,#16213e,#1a1a2e);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;"><i class="bi bi-cup-straw"></i></div>
                <div class="masonry-overlay" onclick="openLightbox('', 'Fine Dining Restaurant')">
                    <div><span class="masonry-category">Restaurant</span><h4 class="masonry-title">Fine Dining Restaurant</h4></div>
                </div>
            </div>
            <div class="masonry-item" data-category="exterior">
                <div style="width:100%;height:320px;background:linear-gradient(135deg,#1a1a2e,#16213e);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;"><i class="bi bi-water"></i></div>
                <div class="masonry-overlay" onclick="openLightbox('', 'Infinity Pool')">
                    <div><span class="masonry-category">Exterior</span><h4 class="masonry-title">Infinity Pool</h4></div>
                </div>
            </div>
            <div class="masonry-item" data-category="other">
                <div style="width:100%;height:220px;background:linear-gradient(135deg,#0a0a1a,#16213e);display:flex;align-items:center;justify-content:center;color:var(--gold);font-size:3rem;"><i class="bi bi-people"></i></div>
                <div class="masonry-overlay" onclick="openLightbox('', 'Executive Lounge')">
                    <div><span class="masonry-category">Other</span><h4 class="masonry-title">Executive Lounge</h4></div>
                </div>
            </div>
        </div>
        <?php endif; ?>
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
    var items = document.querySelectorAll('.masonry-item');

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

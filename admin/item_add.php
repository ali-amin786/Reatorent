<?php
// admin/item_add.php - Simple Single-Column Add Dish Form
$page_title = "Add New Item";
require_once __DIR__ . '/header.php';

$error = "";
$success = "";

// Fetch categories
$cats_res = mysqli_query($conn, "SELECT * FROM categories ORDER BY id ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token mismatch.";
    } else {
        // Inline "Add New Category" logic
        if (($_POST['category_id'] ?? '') === '__new__') {
            $new_cat_name = trim(clean($conn, $_POST['new_category_name'] ?? ''));
            if (!empty($new_cat_name)) {
                mysqli_query($conn, "INSERT INTO categories (name) VALUES ('$new_cat_name')");
                $_POST['category_id'] = (string)mysqli_insert_id($conn);
            } else {
                $_POST['category_id'] = '0'; // triggers validation error below
            }
        }
        $category_id = clean_int($_POST['category_id'] ?? 0);
        $title = clean($conn, $_POST['title'] ?? '');
        $description = clean($conn, $_POST['description'] ?? '');
        $price = clean_float($_POST['price'] ?? 0);
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        if ($category_id <= 0) {
            $error = "Please choose a category.";
        } elseif (empty($title)) {
            $error = "Please enter the dish title.";
        } elseif ($price <= 0) {
            $error = "Please enter a valid price.";
        } else {
            // Handle image upload
            $image_filename = 'beef_chapli_single.jpg'; // fallback
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_res = handle_image_upload(
                    $_FILES['image'], 
                    __DIR__ . '/../assets/uploads/items/', 
                    'item_'
                );
                if ($upload_res['success']) {
                    $image_filename = clean($conn, $upload_res['filename']);
                } else {
                    $error = "Image upload failed: " . $upload_res['error'];
                }
            }

            if (empty($error)) {
                $ins_sql = "INSERT INTO items (category_id, title, description, price, image_url, is_available) 
                            VALUES ($category_id, '$title', '$description', $price, '$image_filename', $is_available)";
                if (mysqli_query($conn, $ins_sql)) {
                    header("Location: items.php?msg=added");
                    exit;
                } else {
                    $error = "Database error: " . mysqli_error($conn);
                }
            }
        }
    }
}
?>

<div style="margin-bottom: 16px;">
    <a href="items.php" style="color: var(--admin-text-muted); font-size: 0.85rem;">&larr; Back to menu items</a>
</div>

<div class="admin-card" style="max-width: 600px; padding: 28px;">
    <h2 style="font-size: 1.25rem; margin-bottom: 20px;">New Menu Dish</h2>

    <?php if (!empty($error)): ?>
        <div style="background: var(--admin-primary-light); border: 1px solid rgba(227,28,35,0.3); color: var(--admin-primary); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 18px; font-size: 0.88rem;">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="item_add.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="admin-form-group">
            <label for="title" class="admin-form-label">Dish Title / Name *</label>
            <input type="text" id="title" name="title" class="admin-form-control" required placeholder="e.g. Peshawari Beef Chapli Kabab (2 Pcs)" value="<?php echo e($_POST['title'] ?? ''); ?>">
        </div>

        <div class="admin-form-group">
            <label for="category_id" class="admin-form-label">Category *</label>
            <select name="category_id" id="category_id" class="admin-form-control" required>
                <option value="">-- Select Category --</option>
                <?php while ($cat = mysqli_fetch_assoc($cats_res)): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo (isset($_POST['category_id']) && (int)$_POST['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                        <?php echo e($cat['name']); ?>
                    </option>
                <?php endwhile; ?>
                <option value="__new__" <?php echo (($_POST['category_id'] ?? '') === '__new__') ? 'selected' : ''; ?>>+ Add New Category</option>
            </select>
            <div id="new-cat-wrap" style="display:none;margin-top:8px;">
                <input type="text" name="new_category_name" id="new_category_name" class="admin-form-control" placeholder="New category name..." value="<?php echo e($_POST['new_category_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="admin-form-group">
            <label for="price" class="admin-form-label">Price (PKR) *</label>
            <input type="number" id="price" name="price" step="1" min="1" class="admin-form-control" required placeholder="e.g. 480" value="<?php echo e($_POST['price'] ?? ''); ?>">
        </div>

        <div class="admin-form-group">
            <label for="description" class="admin-form-label">Description (Optional)</label>
            <textarea id="description" name="description" class="admin-form-control" style="min-height: 75px;" placeholder="Fresh beef coarsely hand-minced with crushed coriander, anardana, and bone marrow tallow..."><?php echo e($_POST['description'] ?? ''); ?></textarea>
        </div>

        <div class="admin-form-group">
            <label for="image" class="admin-form-label">Dish Photo (Max 2MB, JPG/PNG)</label>
            <input type="file" id="image" name="image" class="admin-form-control" accept="image/jpeg,image/png,image/webp">
            <div id="imgPreviewWrap" style="margin-top: 10px; display: none;">
                <img id="imgPreview" src="" alt="Preview" style="max-height: 140px; border-radius: 6px; border: 1px solid var(--admin-border);">
            </div>
        </div>

        <div class="admin-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
            <input type="checkbox" id="is_available" name="is_available" value="1" checked style="width: 18px; height: 18px;">
            <label for="is_available" style="font-weight: 500; cursor: pointer;">
                In Stock (Available immediately for ordering)
            </label>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                Save Item
            </button>
            <a href="items.php" class="btn-admin btn-admin-secondary">
                Cancel
            </a>
        </div>
    </form>
</div>

<script>
const fileInput = document.getElementById('image');
const previewWrap = document.getElementById('imgPreviewWrap');
const previewImg = document.getElementById('imgPreview');

if (fileInput && previewWrap && previewImg) {
    fileInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                previewWrap.style.display = 'block';
            };
            reader.readAsDataURL(file);
        } else {
            previewWrap.style.display = 'none';
        }
    });
}

// Category inline-add toggle
const catSelect   = document.getElementById('category_id');
const newCatWrap  = document.getElementById('new-cat-wrap');
const newCatInput = document.getElementById('new_category_name');
function toggleNewCat() {
    const isNew = catSelect.value === '__new__';
    newCatWrap.style.display = isNew ? 'block' : 'none';
    if (newCatInput) newCatInput.required = isNew;
}
if (catSelect) {
    catSelect.addEventListener('change', toggleNewCat);
    toggleNewCat(); // run on load in case of POST-back
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

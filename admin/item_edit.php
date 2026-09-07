<?php
// admin/item_edit.php - Simple Single-Column Edit Dish Form
$item_id = isset($_GET['id']) ? clean_int($_GET['id']) : 0;
if ($item_id <= 0) {
    header("Location: items.php");
    exit;
}

$page_title = "Edit Item";
require_once __DIR__ . '/header.php';

// Fetch item
$res = mysqli_query($conn, "SELECT * FROM items WHERE id = $item_id LIMIT 1");
if (!$res || mysqli_num_rows($res) === 0) {
    echo "<div class='admin-card'>Item not found. <a href='items.php'>Back to items</a></div>";
    require_once __DIR__ . '/footer.php';
    exit;
}
$item = mysqli_fetch_assoc($res);

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
                $_POST['category_id'] = '0';
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
            $image_filename = $item['image_url'];

            // Optional image replace
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $upload_res = handle_image_upload(
                    $_FILES['image'], 
                    __DIR__ . '/../assets/uploads/items/', 
                    'item_'
                );
                if ($upload_res['success']) {
                    $new_image = clean($conn, $upload_res['filename']);
                    // Delete old file only after new upload succeeds
                    if (!empty($item['image_url']) && strpos($item['image_url'], 'item_') === 0) {
                        $old_path = __DIR__ . '/../assets/uploads/items/' . $item['image_url'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                    $image_filename = $new_image;
                } else {
                    $error = "Image upload failed: " . $upload_res['error'];
                }
            }

            if (empty($error)) {
                $upd_sql = "UPDATE items SET 
                            category_id = $category_id, 
                            title = '$title', 
                            description = '$description', 
                            price = $price, 
                            image_url = '$image_filename', 
                            is_available = $is_available 
                            WHERE id = $item_id";

                if (mysqli_query($conn, $upd_sql)) {
                    $success = "Item updated successfully.";
                    // Refresh data
                    $res = mysqli_query($conn, "SELECT * FROM items WHERE id = $item_id LIMIT 1");
                    $item = mysqli_fetch_assoc($res);
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
    <h2 style="font-size: 1.25rem; margin-bottom: 20px;">Edit: <?php echo e($item['title']); ?></h2>

    <?php if (!empty($success)): ?>
        <div style="background: var(--admin-success-light); border: 1px solid rgba(30,142,62,0.3); color: var(--admin-success); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 18px; font-size: 0.88rem;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background: var(--admin-primary-light); border: 1px solid rgba(227,28,35,0.3); color: var(--admin-primary); padding: 10px 14px; border-radius: var(--radius-sm); margin-bottom: 18px; font-size: 0.88rem;">
            <?php echo e($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="item_edit.php?id=<?php echo $item_id; ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="admin-form-group">
            <label for="title" class="admin-form-label">Dish Title / Name *</label>
            <input type="text" id="title" name="title" class="admin-form-control" required value="<?php echo e($item['title']); ?>">
        </div>

        <div class="admin-form-group">
            <label for="category_id" class="admin-form-label">Category *</label>
            <select name="category_id" id="category_id" class="admin-form-control" required>
                <?php while ($cat = mysqli_fetch_assoc($cats_res)): ?>
                    <option value="<?php echo (int)$cat['id']; ?>" <?php echo ((int)$item['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                        <?php echo e($cat['name']); ?>
                    </option>
                <?php endwhile; ?>
                <option value="__new__">+ Add New Category</option>
            </select>
            <div id="new-cat-wrap" style="display:none;margin-top:8px;">
                <input type="text" name="new_category_name" id="new_category_name" class="admin-form-control" placeholder="New category name..." value="<?php echo e($_POST['new_category_name'] ?? ''); ?>">
            </div>
        </div>

        <div class="admin-form-group">
            <label for="price" class="admin-form-label">Price (PKR) *</label>
            <input type="number" id="price" name="price" step="1" min="1" class="admin-form-control" required value="<?php echo (float)$item['price']; ?>">
        </div>

        <div class="admin-form-group">
            <label for="description" class="admin-form-label">Description (Optional)</label>
            <textarea id="description" name="description" class="admin-form-control" style="min-height: 75px;"><?php echo e($item['description']); ?></textarea>
        </div>

        <div class="admin-form-group">
            <label class="admin-form-label">Current Photo</label>
            <div style="margin-bottom: 10px;">
                <img src="../assets/uploads/items/<?php echo e($item['image_url']); ?>" alt="" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--admin-border);">
            </div>
            <label for="image" class="admin-form-label">Replace Photo (Optional, Max 2MB)</label>
            <input type="file" id="image" name="image" class="admin-form-control" accept="image/jpeg,image/png,image/webp">
            <div id="imgPreviewWrap" style="margin-top: 10px; display: none;">
                <img id="imgPreview" src="" alt="Preview" style="max-height: 120px; border-radius: 6px; border: 1px solid var(--admin-border);">
            </div>
        </div>

        <div class="admin-form-group" style="display: flex; align-items: center; gap: 10px; margin-top: 10px;">
            <input type="checkbox" id="is_available" name="is_available" value="1" <?php echo $item['is_available'] ? 'checked' : ''; ?> style="width: 18px; height: 18px;">
            <label for="is_available" style="font-weight: 500; cursor: pointer;">
                In Stock (Available for ordering)
            </label>
        </div>

        <div style="display: flex; gap: 12px; margin-top: 24px;">
            <button type="submit" class="btn-admin btn-admin-primary" style="padding: 10px 24px;">
                Update Item
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
    toggleNewCat();
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

<?php include VIEWS_PATH . 'layout.php'; ?>

<form action="convert" method="POST" enctype="multipart/form-data">
    <input type="file" name="filename_u">
    <input type="submit" value="upload">
</form>
<form action="convert" method="POST">
    <select name="filename">
        <?php foreach ($filenames as $filename): ?>
            <option value="<?php echo $filename; ?>"><?php echo $filename; ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="convert">
</form>
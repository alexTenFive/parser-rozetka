<form action="convert" method="POST">
    <select name="filename">
        <?php foreach ($filenames as $filename): ?>
            <option value="<?php echo $filename; ?>"><?php echo $filename; ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="convert">
</form>
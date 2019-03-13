<form action="write" method="POST">
    <select name="category" required>
    <?php foreach ($categories as $category): ?>
        <option value="<?php echo $category['id'] ?>"><?php echo $category['name'] ?></option>
    <?php endforeach; ?>
    </select>
    <input type="text" name="filename" required>
    <input type="submit" value="write">
</form>
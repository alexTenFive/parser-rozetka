<?php include VIEWS_PATH . 'header.php'; ?>
<div class="container" style="max-width: 95%">
    <div class="row">
        <div class="col-md-3">
            <a href="/parse" class="btn btn-info float-left mb-1 mt-1">Парсить</a>
            <a href="/convert" class="btn btn-info">Конвертировать Excel в YML</a>
        </div>
    <div class="col-md-6">
        <form action="write" method="POST">
        <div class="form-group">
            <label for="exampleFormControlSelect1">Выберите категорию товаров для записи:</label>

            <select name="category" class="form-control" id="exampleFormControlSelect1" required>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo $category['id'] ?>"><?php echo $category['name'] ?></option>
            <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="exampleFormControlSelect1">Введите название файла:</label>
            <input type="text" name="filename" class="form-control" required>
        </div>
        <div class="form-group mt-1">
            <input type="submit" class="form-control btn btn-dark" value="Записить">
        </div>
        </form>
    </div>
    </div>
<div>
<?php include VIEWS_PATH . 'footer.php'; ?>

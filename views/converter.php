<?php include VIEWS_PATH . 'layout.php'; ?>
<div class="container" style="max-width:95%">
    <div class="row">
        <div class="col-md-2">
            <a href="/parse" class="btn btn-info float-left mb-1 mt-1">Парсить</a>
            <a href="/write" class="btn btn-info">Записать в Excel</a>
        </div>
        <div class="col-md-7">
            <form action="convert" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="file_upload">Загрузите Excel файл:</label>
                    <input type="file" class="form-control-file" name="filename_u" id="file_upload">
                </div>
                <?php if (isset($error) && !empty($error)): ?>
                    <p class="text-danger small"><?php echo $error; ?></p>
                <?php endif; ?>
                <input type="submit" value="Загрузить" class="form-control btn btn-dark">
            </form>
            <form action="convert" method="POST">
            <div class="form-group">
            <select name="filename" class="form-control">
                    <?php foreach ($filenames as $filename): ?>
                        <option value="<?php echo $filename; ?>"><?php echo $filename; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
                <div class="form-control btn btn-danger">
                    <input type="submit" class="form-control btn btn-dark" value="Конвертировать">        
                </div>
            </form>
        </div>
    </div>
</div>

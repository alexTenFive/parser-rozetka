<?php include VIEWS_PATH . 'header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-2">
            <a href="/parse" class="btn btn-info float-left mb-1 mt-1">Парсить</a>
            <a href="/write" class="btn btn-info float-left mb-1 mt-1">Записать в Excel</a>
            <a href="/convert" class="btn btn-info">Конвертировать Excel в YML</a>
        </div>
        <div class="col-md-7">
            <form action="/proxy" method="POST">
                <div class="form-group"><textarea name="proxies" id="" cols="25" rows="25" class="form-control" style="width: 100%"><?php foreach ($proxies as $proxy): ?><?php echo $proxy; ?><?php endforeach; ?></textarea></div>
                <input type="submit" class="form-control btn btn-warning float-right mb-1" value="Изменить">
            </form>
        </div>
    </div>
</div>
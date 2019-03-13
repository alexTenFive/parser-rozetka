<form action="parse" method="POST">
    <input name="links">
    <input type="hidden" name="XDEBUG_PROFILE" value="1">
    <input type="submit" value="parse"  onclick="location.reload">
</form>
<p>Parsing in process...</p>
<p>Parsed products count: <span id="count"></span></p>

<progress min="0" max="100" value="0" style="height: 40px;width: 300px;"></progress>
<span id="progress"></span>
<script>
var exectime = 4000;

var requests = 0;
(function check() {

        if (requests > 3) {
            location.reload();
        }

    var start_time = new Date().getTime();

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/check-count');

    xhr.onload = function() {
        if (xhr.status === 200) {
            res = JSON.parse(xhr.responseText);
            document.getElementById('count').innerHTML = res.countProducts;
            progress = ((res.countProducts / (res.pagesCount * 32)) * 100).toFixed(2);
            document.querySelector('progress').value = progress;
            document.getElementById('progress').innerHTML = progress + '%';
        }
        else {
            alert('Request failed.  Returned status of ' + xhr.status);
        }
    };
    xhr.onloadend = function () {
        requests++;
        setTimeout(check, exectime);
    }
    xhr.send();
}());
</script>
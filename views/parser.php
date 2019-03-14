<?php include VIEWS_PATH . 'layout.php'; ?>
<style>
progress:not(value) {
    /* Add your styles here. As part of this walkthrough we will focus only on determinate progress bars. */
}

/* Styling the determinate progress element */

progress[value] {
    /* Get rid of the default appearance */
    appearance: none;
    
    /* This unfortunately leaves a trail of border behind in Firefox and Opera. We can remove that by setting the border to none. */
    border: none;
    
    /* Add dimensions */
    width: 100%; height: 20px;
    
    /* Although firefox doesn't provide any additional pseudo class to style the progress element container, any style applied here works on the container. */
      background-color: whiteSmoke;
      border-radius: 3px;
      box-shadow: 0 2px 3px rgba(0,0,0,.5) inset;
    
    /* Of all IE, only IE10 supports progress element that too partially. It only allows to change the background-color of the progress value using the 'color' attribute. */
    color: royalblue;
    
    position: relative;
    margin: 0 0 1.5em; 
}

/*
Webkit browsers provide two pseudo classes that can be use to style HTML5 progress element.
-webkit-progress-bar -> To style the progress element container
-webkit-progress-value -> To style the progress element value.
*/

progress[value]::-webkit-progress-bar {
    background-color: whiteSmoke;
    border-radius: 3px;
    box-shadow: 0 2px 3px rgba(0,0,0,.5) inset;
}

progress[value]::-webkit-progress-value {
    position: relative;
    
    background-size: 35px 20px, 100% 100%, 100% 100%;
    border-radius:3px;
    
    /* Let's animate this */
    animation: animate-stripes 5s linear infinite;
}

@keyframes animate-stripes { 100% { background-position: -100px 0; } }

/* Let's spice up things little bit by using pseudo elements. */

progress[value]::-webkit-progress-value:after {
    /* Only webkit/blink browsers understand pseudo elements on pseudo classes. A rare phenomenon! */
    content: '';
    position: absolute;
    
    width:5px; height:5px;
    top:7px; right:7px;
    
    background-color: white;
    border-radius: 100%;
}

/* Firefox provides a single pseudo class to style the progress element value and not for container. -moz-progress-bar */

progress[value]::-moz-progress-bar {
    /* Gradient background with Stripes */
    background-image:
    -moz-linear-gradient( 135deg,
                                                     transparent,
                                                     transparent 33%,
                                                     rgba(0,0,0,.1) 33%,
                                                     rgba(0,0,0,.1) 66%,
                                                     transparent 66%),
    -moz-linear-gradient( top,
                                                        rgba(255, 255, 255, .25),
                                                        rgba(0,0,0,.2)),
     -moz-linear-gradient( left, #09c, #f44);
    
    background-size: 35px 20px, 100% 100%, 100% 100%;
    border-radius:3px;
    
    /* Firefox doesn't support CSS3 keyframe animations on progress element. Hence, we did not include animate-stripes in this code block */
}

/* Fallback technique styles */
div.block {
    position: relative;
    width: 100%;
    margin: 0 auto;
}

#progress-bar {
    position: absolute;
    width: 20px;
    height: 10px;
    top: -2px;
    left: 48%;
}

p[data-value] { 
  
  position: relative; 
}

/* The percentage will automatically fall in place as soon as we make the width fluid. Now making widths fluid. */

p[data-value]:after {
    content: attr(data-value) '%';
    position: absolute; right:0;
}

progress::-webkit-progress-value 
{
    /* Gradient background with Stripes */
    background-image:
    -webkit-linear-gradient( 135deg,
                                                     transparent,
                                                     transparent 33%,
                                                     rgba(0,0,0,.1) 33%,
                                                     rgba(0,0,0,.1) 66%,
                                                     transparent 66%),
    -webkit-linear-gradient( top,
                                                        rgba(255, 255, 255, .25),
                                                        rgba(0,0,0,.2)),
     -webkit-linear-gradient( left, #09c, #ff0);
}

/* Similarly, for Mozillaa. Unfortunately combining the styles for different browsers will break every other browser. Hence, we need a separate block. */

progress::-moz-progress-bar {
    /* Gradient background with Stripes */
    background-image:
    -moz-linear-gradient( 135deg,
                                                     transparent,
                                                     transparent 33%,
                                                     rgba(0,0,0,.1) 33%,
                                                     rgba(0,0,0,.1) 66%,
                                                     transparent 66%),
    -moz-linear-gradient( top,
                                                        rgba(255, 255, 255, .25),
                                                        rgba(0,0,0,.2)),
     -moz-linear-gradient( left, #09c, #f44);
}

progress::-moz-progress-bar {
{
    /* Gradient background with Stripes */
    background-image:
    -moz-linear-gradient( 135deg,
                                                     transparent,
                                                     transparent 33%,
                                                     rgba(0,0,0,.1) 33%,
                                                     rgba(0,0,0,.1) 66%,
                                                     transparent 66%),
    -moz-linear-gradient( top,
                                                        rgba(255, 255, 255, .25),
                                                        rgba(0,0,0,.2)),
     -moz-linear-gradient( left, #09c, #ff0);
}

</style>

<div class="container" style="max-width: 95%">
    <div class="row">
    <div class="col-md-3">
        <a href="/write" class="btn btn-info float-left mb-1 mt-1">Записать в Excel</a>
        <a href="/convert" class="btn btn-info">Конвертировать Excel в YML</a>
    </div>
    <div class="col-md-6">
        <form action="parse" method="POST">
            <div class="form-group">
                <label for="exampleFormControlFile1">Вставьте ссылку на категорию:</label>
                <input name="links" class="form-control" id="exampleFormControlFile1">
                <input type="hidden" name="XDEBUG_PROFILE" value="1">
            </div>
            <div class="form-group">
            <input type="submit" value="Парсить" class="form-control btn btn-dark">
            </div>
        </form>
        </div>

    </div>
    <div class="row">
    <div class="col-md-6 offset-md-3">
        <p>Количество скачаных продуктов: <span id="count"></span></p>
        <div class="block">
        <progress min="0" max="100" value="0">
        </progress>
        <div id="progress-bar"><span id="progress-bar-val"></span></div>
        </div>
    </div>
    </div>
    
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
            document.getElementById('progress-bar-val').innerHTML = progress + '%';
            if (res.complete === 1) {
            document.querySelector('progress').value = 100;
            document.getElementById('progress-bar-val').innerHTML = '100%';

            }
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
})();
</script>
</div>

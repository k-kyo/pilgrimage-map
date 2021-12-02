<?php
$dbconn = pg_connect("host=localhost dbname=s1922023 user=s1922023 password=2oKcrRab") or die('Could not connect: ' . pg_last_error());
$route = array();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MAP</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Raleway:700,400">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/normalize.css">
    <link rel="stylesheet" href="css/style.css">
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A==" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js" integrity="sha512-XQoYMqMTK8LvdxXYG3nZ448hOEQiglfqkJs1NOQV44cWnUrBc8PkAOcXy20w0vlaXaVUearIOBhiXZ5V3ynxwA==" crossorigin=""></script>
    <script>
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                var lon = pos.coords.longitude;
                var lat = pos.coords.latitude;
                var targets = document.querySelectorAll('form');
                for (let i = 0; i < targets.length; i++) {
                    targets[i].action = `?lon=${lon}&lat=${lat}#result`;
                }
            }
        );
    </script>
</head>
<body>
    <header class="header">
        <nav class="global-nav">
            <ul>
                <li class="nav-item"><a href="#home">時空間データベース</a></li>
            </ul>
        </nav>
        <div class="hamburger-menu">
            <input type="checkbox" id="menu-btn-check">
            <label for="menu-btn-check" class="menu-btn"><span></span></label>
            <div class="menu-content">
                <ul>
                    <li class="nav-item"><a href="#home">時空間データベース</a></li>
                </ul>
            </div>
        </div>
    </header>
    <section class="home" id="home">
        <div class="site-title-sub-wrapper"><p class="site-title-sub">HELLO WORLD</p></div>
        <h1 class="site-title">アニメ聖地巡礼</h1>
        <div class="site-description-wrapper"><p class="site-description">Welcome to my site</p></div>
    </section>
    <section class="about" id="about">
        <h2 class="heading">ABOUT</h2>
        <p class="about-text">
            アニメの聖地を最短距離で巡るルートを提案します。
        </p>
    </section>
    <section class="titles" id="titles">
        <h2 class="heading">TITLE</h2>
        <div class="title-wrapper clearfix">
            <?php
            $i = 0;
            $query = "select * from title;";
            $res = pg_query($query) or die("Query failed: " . pg_last_error());
            while ($row = pg_fetch_array($res)) {
            ?>
                <div class="title-box">
                    <h3 class="title"><?php echo $row['title']; ?></h3>
                    <p class="desc"><a href="<?php echo $row['url']; ?>">公式サイト</a></p>
                    <img class="image" src="<?php echo $row['img']; ?>">
                    <form method="POST" name="form" action="#result">
                        <input type="hidden" name="title" value="<?php echo $row["title"]; ?>">
                        <a href="javascript:form[<?php echo $i; ?>].submit()">ルート探索</a>
                    </form>
                </div>
            <?php
                $i++;
            }
            ?>
        </div>
    </section>
    <?php
    if (isset($_POST["title"])) {
        $title = $_POST["title"];
        $lon = $_GET["lon"] ? $_GET["lon"] : 139.7862498;
        $lat = $_GET["lat"] ? $_GET["lat"] : 35.6313382;
        $query = "SELECT place, lon, lat FROM pgr_TSPeuclidean('SELECT id, lat x, lon y FROM map UNION SELECT 0, {$lon}, {$lat}', 0) AS a, (SELECT id, place, lon, lat FROM map WHERE map.title = '{$title}' UNION SELECT 0, '現在地', {$lon}, {$lat}) AS b WHERE a.node = b.id;";
        $res = pg_query($query) or die("Query failed: " . pg_last_error());
        while ($row = pg_fetch_row($res)) {
            array_push($route, $row);
        }
    ?>
        <section class="result" id="result">
            <h2 class="heading">RESULT</h2>
            <p class="result-text">
                <span style="font-weight: bold;"><?php echo $title; ?></span>の聖地を巡る最短ルートは
            </p>
            <p class="result-text">
                <?php
                for($i = 0; $i < count($route) - 1; $i++) {
                    echo "{$route[$i][0]} -> ";
                }
                echo "{$route[$i][0]}";
                ?>
            </p>
        </section>
    <?php
        echo "<div id='map'></div>";
    }
    ?>
    <footer class="footer">
        &copy; 2021 Yota Nakamura
    </footer>
    <script>
        var map = L.map("map");
        var tileLayer = L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy;<a href='http://osm.org/copyright'>OpenStreetMap</a> contributors, <a href='http://creativecommons.org/licenses/by-sa/2.0/'>CC-BY-SA</a>",
        });
        tileLayer.addTo(map);

        var iconMarkers = L.featureGroup();
        <?php
        for($i = 0; $i < count($route) - 1; $i++) {
            echo "var popup = L.popup({closeOnClick: false, autoClose: false});";
            echo "popup.setContent('<p>{$route[$i][0]}</p>');";
            echo "iconMarkers.addLayer(L.marker([{$route[$i][2]}, {$route[$i][1]}]).addTo(map).bindPopup(popup));";
        }
        ?>
         iconMarkers.addTo(map);
         map.fitBounds(iconMarkers.getBounds());
    </script>
</body>
</html>

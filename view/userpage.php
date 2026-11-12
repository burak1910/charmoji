<?php
// view/userpage.php
// Eski tek sayfalık panel. Arayüz beş sekmeye bölündü (Karargah, Görevler, Market,
// Lonca, Karakter); eski adres kırılmasın diye Karargah'a yönlendirir. POST de
// işlenmez (görev ekleme artık view/gorevler.php'de): 303 ile Karargah'a GET olarak gider.
header('Location: karargah.php', true, 303);
exit();

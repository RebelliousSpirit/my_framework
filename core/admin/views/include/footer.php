            </div><!--.vg-main.vg-right-->
        </div><!--.vg-carcass-->
            <div class="vg-modal vg-center">
                <?php
                   // если есть сообщение о ошибке
                    if ($_SESSION['res']['answer']){
                        echo $_SESSION['res']['answer'];
                        unset($_SESSION['res']);
                    }
                ?>
            </div>
            <script>
                const PATH = '<?=PATH?>';
                // флаг по которому в core\base\controllers\BaseAjaxController определяется что работа идет с админ.
                // частью сайта
                const ADMIN_MODE = 1;
            </script>
            <?php
            // метод находится в core/controllers/baseMethods.php
                $this->getScripts();
            ?>
    </body>
</html>
<div class="vg-wrap vg-element vg-ninteen-of-twenty">
    <?/* кнопка добавления */?>
    <div class="vg-element vg-fourth">
        <a href="<?=$this->adminPath?>add/<?=$this->table?>"
           class="vg-wrap vg-element vg-full vg-firm-background-color3 vg-box-shadow">
            <span class="vg-element vg-half vg-center">
                <img src="<?=PATH . ADMIN_TEMPLATES?>img/plus.png" alt="plus">
            </span>
            <span class="vg-element vg-half vg-center vg-firm-background-color1">
                <span class="vg-text vg-firm-color3">Add</span>
            </span>
        </a>
    </div>
    <?/* элементы контента админки */?>
    <? if ($this->data): ?>
        <? foreach ($this->data as $data):?>
            <div class="vg-element vg-fourth">
                <a href="<?=$this->adminPath?>edit/<?=$this->table?>/<?=$data['id']?>"
                   class="vg-wrap vg-element vg-full vg-firm-background-color4 vg-box-shadow show_element">
                    <div class="vg-element vg-half vg-center">
                        <? if ($data['img']): ?>
                        <img src="<?=PATH . UPLOAD_DIR . $data['img']?>" alt="service">
                        <? endif; ?>
                    </div>
                    <div class="vg-element vg-half vg-center">
                        <span class="vg-text vg-firm-color1"><?= $data['name']?></span>
                    </div>
                </a>
            </div>
        <? endforeach;?>
    <? endif; ?>
</div>
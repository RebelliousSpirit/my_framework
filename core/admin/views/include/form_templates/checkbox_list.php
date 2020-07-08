<div class="vg-element vg-full vg-left vg-box-shadow">
    <div class="vg-wrap vg-element vg-full vg-box-shadow">
        <div class="vg-element vg-full vg-left">
            <span class="vg-header ui-sortable-handle"><?=$this->translate[$row][0] ?: $row?></span>
        </div>
        <div class="vg-element vg-full vg-input vg-relative vg-space-between select_wrap">
            <span class="vg-text vg-left">Выбрать</span>
            <span class="vg-text vg-right select_all">Выделить все</span>
        </div>
        <div class="option_wrap">
            <label class="custom_label" for="filters-1">
                <input id="filters-1" type="checkbox" name="filters[filters][]" value="1">
                <span class="custom_check backgr_bef"></span><span class="label">Что то первое</span>
            </label>
            <label class="custom_label" for="filters-2">
                <input id="filters-2" type="checkbox" name="filters[filters][]" value="2">
                <span class="custom_check backgr_bef"></span><span class="label">Что то ВТОРОЕ</span>
            </label>
        </div>
    </div>
</div>


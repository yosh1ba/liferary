<section id="sidebar-left" class="col-3 pt-5">
    <form action="" method="get">
        <div class="form-group mb-4">
            <label for="select-age" class="ml-1">絞り込み</label>
            <select class="form-control" id="select-period" name="period_id">
                <option value="0" <?php if(getFormData('period_id',true) == 0){echo 'selected';} ?>>選択してください</option>
                <?php
                foreach($dbPeriodCategoryData as $key => $val){
                ?>
                <option value="<?php echo $val['id'] ?>" <?php if(getFormData('period_id', true) == $val['id']){ echo 'selected';} ?> >
                    <?php echo $val['name']; ?>
                </option>
                <?php
                }
                ?>
            </select>
        </div>
        <div class="form-group mb-4">
        <label for="select-sort" class="ml-1">並び替え</label>
        <select class="form-control" id="select-sort" name="sort">
            <option value="0" <?php if(getFormData('sort',true) == 0){echo 'selected';} ?>>選択してください</option>
            <option value="1" <?php if(getFormData('sort',true) == 1){echo 'selected';} ?>>投稿日の新しい順</option>
            <option value="2" <?php if(getFormData('sort',true) == 2){echo 'selected';} ?>>投稿日の古い順</option>
        </select>
        </div>
        <button type="submit" id="refine-btn" class="btn btn-success rounded-0 px-4 py-2">検索</button>
    </form>
</section>

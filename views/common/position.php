<div class='block-container'>
    <div class='container-content font-14'>
    <?php
        if($this->params['position'] != null){
            echo '所在位置：&nbsp;&nbsp;'.$this->params['position']->toHtml(); 
        } 
    ?>
    </div>
    <div class='float-clear'></div>
</div>
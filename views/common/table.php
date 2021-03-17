<?php
use yii\widgets\LinkPager;
$pagination = '';
if($this->params['table']->pagination != null){
    $pagination = LinkPager::widget([
      'pagination' => $this->params['table']->pagination,
    ]);
}
?>
<div class='table-container'>
    <?php if($this->params['table']->pagination != null)?>
    <?= $pagination ?>
    <?php echo $this->params['table']->toHtml(); ?>
    <?= $pagination ?>
</div>
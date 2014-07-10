<?php 
$mvc = EhrlichAndreas_Util_Mvc::getInstance();

$router = $mvc->getRouter();

$userParams = array
(
    'newsletter_id' => 17,
    'title'         => 'fasdf af asD',
);
?>
<div>
    <form action="<?php echo $router->assemble($userParams, 'newsletter', true, true);?>" method="post">
        <p><input type="text" name="mail" value="<?php echo $this->escape($this->invokeParams['mail']);?>" /></p>
        <p><input type="text" name="name" value="<?php echo $this->escape($this->invokeParams['name']);?>" /></p>
        <p><input type="submit" name="submit" value="submit" /></p>
    </form>
</div>

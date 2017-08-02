<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?php wp_title( '|', true, 'right' ); ?></title>

    <?php wp_head();?>
</head>
<body <?php body_class(); ?>>
    <?php
    $retorno = DVM_WPMaintenance::calc_time_maintenance();
    $retorno = $retorno['return-date'];

    echo "<p style='text-align: center; display: block; margin-top: 50px;'>O site está em manutenção.<br/>A previsão de retorno é para <strong> $retorno; </strong></p>";
    ?>

    <?php wp_footer(); ?>
</body>
</html>
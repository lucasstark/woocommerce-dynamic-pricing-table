<?php

$info_message = printf( __( 'Hi %1$s as a %2$s you will receive a %3$s percent discount on all products.', 'woocommerce-dynamic-pricing-table' ), esc_attr( $current_user_display_name ), esc_attr( $current_user_role ), floatval( $role_discount_amount ) );

<?php

$next_user = $_GET['offset'] + 1;
echo "<a href='https://gettheessential.com/teste?offset=" . $next_user . "&total=1&active=" . $_GET['active'] . "'>Next</a>";

$memberships = rcp_get_memberships( array(
    'status' => 'active',
    'number' => (isset($_GET['total'])) ? $_GET['total'] : 10,
    'offset' => (isset($_GET['offset'])) ? $_GET['offset'] : 0

) );

foreach ($memberships as $key => $value) {
    $user_id = rcp_get_customer($value->get_customer()->get_id())->get_user_id();
    
    echo "Usuario: " . get_user_by('id',$user_id)->user_email . "<br>";
    echo "Plano: " . rcp_get_subscription( $user_id ) . "<br>";
    echo "Data do Plano: " . $value->get_created_date() . "<br>";
    echo "Data de Expiração: " . $value->get_expiration_date() . "<br>";
    
    $plan_id = $value->get_object_id();

    switch ($plan_id) {
    case 1:
        $plano = 1763;
        break;
    case 2:
        $plano = 1764;
        break;
    case 3:
        $plano = 1766;
        break;
    case 4:
        $plano = 1765;
        break;
    case 5:
        $plano = 1767;
        break;
    case 6:
        $plano = 1772;
        break;
    }

    if(isset($_GET['active'])) {
        create_sub($value->get_created_date(false), $value->get_expiration_date(false), $user_id, $plano);
    }
 
}



function create_sub($start_date, $end_date, $user_id, $plan_id) {
    
    $user = $user_id;

    $product = wc_get_product($plan_id);

    $quantity = 1;

    $order = wc_create_order(array('customer_id' => $user));

    $order->add_product( $product, $quantity);

    $order->calculate_totals();

    $order->update_status("completed", 'Imported order', TRUE);

    $period = WC_Subscriptions_Product::get_period( $product );
    
    $interval = WC_Subscriptions_Product::get_interval( $product );

    if($plan_id == 1766 or $plan_id == 1768 or $plan_id == 1778) {

        $sub = wcs_create_subscription(array('order_id' => $order->id, 'billing_period' => $period, 'billing_interval' => $interval, 'start_date' => $start_date));

        $dates_to_update = array();
        $dates_to_update['end'] = $end_date;
        $sub->update_dates($dates_to_update);

    } else {

        $sub = wcs_create_subscription(array('order_id' => $order->id, 'billing_period' => $period, 'billing_interval' => $interval, 'start_date' => $start_date));
        $dates_to_update = array();
        $dates_to_update['next_payment'] = $end_date;
        $sub->update_dates($dates_to_update);
    }

    
    $sub->add_product( $product, $quantity);
   
    $sub->calculate_totals();

    WC_Subscriptions_Manager::activate_subscriptions_for_order($order);

    $member = add_memberships( $user_id, $sub->get_id(), $plan_id );

     if($plan_id == 1766 or $plan_id == 1768 or $plan_id == 1778) { 
          update_post_meta($member, "_start_date", $start_date);  
          update_post_meta($member, "_end_date", $end_date);  
     }
}


function add_memberships( $user_id, $subscription_id, $product_id ) {
    if ( function_exists( 'wc_memberships_get_membership_plans' ) ) {
        if ( ! $membership_plans ) {
            $membership_plans = wc_memberships_get_membership_plans();
        }
        foreach ($membership_plans as $plan ) {
            if ( $plan->has_product( $product_id ) ) {
                $member = $plan->grant_access_from_purchase( $user_id, $product_id, $subscription_id );
                return $member;
            }
        }
    }
}

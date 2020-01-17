<?php

// Recupera o ID do usuário a ser renovado

$users_subscriptions = wcs_get_users_subscriptions($_GET['user_id']);

// Recupera o ID do plano do usuário do RCP (1 = Basic e 2 = Premium)

$plan_id = $_GET['plan_id'];

// Encontra o plano ativo do usuário no Woocommerce 

foreach ($users_subscriptions as $subscription){
  	if ($subscription->has_status(array('active'))) {
	  
	  // Encontra o post que agenda a criação de ordem de renovação

	  $the_query = new WP_Query(array("s"=>'{"subscription_id":'.$subscription->get_id().'}', "post_type"=>'scheduled-action', 'post_status'=>'pending'));
		if ( $the_query->have_posts() ) {
			while ( $the_query->have_posts() ) : $the_query->the_post();
				// ID do post
				$post_renew = get_the_ID();
				// Meta key com a data de renovação 
				$metakey_renew = serialize(get_post_meta( get_the_ID(), '_action_manager_schedule' )[0]);
			endwhile;
		} 
	    
	    // Define a nova data de pagamento com base no plano do usuário

	  	$duration = ($plan_id == 1) ? "+1 month" : "+1 year";
		$next_payment = date('Y-m-d H:i:s', strtotime($duration,strtotime($subscription->get_data()['schedule_next_payment']->date('Y-m-d H:i:s'))));
	    update_post_meta( $subscription->get_id(), '_schedule_next_payment', $next_payment ); 


	    // Altera a data de agendamento do post de ordem de renovação para a próxima data de pagamento

	    $old_payment_unix = strtotime(get_gmt_from_date($subscription->get_data()['schedule_next_payment']->date('Y-m-d H:i:s')));
		$next_payment_unix = strtotime($next_payment);
		$new_metakey_renew = str_replace($old_payment_unix,$next_payment_unix,$metakey_renew);
	    update_post_meta( $post_renew, '_action_manager_schedule', $new_metakey_renew ); 
	    $update_post = array( 'ID' => $post_renew, 'post_date' => $next_payment, 'post_date_gmt' => get_gmt_from_date($next_payment));
	    wp_update_post( $update_post );
	    	 
	}
}

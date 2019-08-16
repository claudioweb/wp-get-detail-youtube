<?php 
/***************************************************************************
Plugin Name:  Get Detail Youtube
Plugin URI:   https://www.superare.co/
Description:  Plugin para trazer informações dos videos
Version:      1.0
Author:       Claudio Web (claudioweb)
Author URI:   http://www.claudioweb.com.br/
Text Domain:  get-detail-youtube
**************************************************************************/

Class GetDetailYoutube {

	private $name_plugin;

	private $fields;

	public function __construct() {

		$this->name_plugin = 'Get Detail YT';
		
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		if(!empty($_POST['salvar'])){
			// die(var_dump($_POST));

			unset($_POST['salvar']);
			foreach ($_POST as $key_field => $value_field) {
				update_option( $key_field, $value_field );
			}

			header('Location:'.admin_url('admin.php?page='.sanitize_title($this->name_plugin)));
			exit;
		
		}

		add_action('get_detail_youtube', array($this, 'get_videos_wp'));
		// add_action('init', array($this, 'get_anos_videos'));
	}

	public function add_admin_menu(){

		add_menu_page(
			$this->name_plugin,
			$this->name_plugin,
			'manage_options', 
			sanitize_title($this->name_plugin), 
			array($this,'meu_plugin_home'), 
    		'dashicons-playlist-video', //URL ICON
    		42 // Ordem menu
    		);
	}

	public function meu_plugin_home(){

		$post_types = get_post_types();

		$p_types = array();

		foreach ( get_post_types( '', 'names' ) as $post_type ) {
			$p_types[] = $post_type;
		 }
		 $this->fields = array(
			'app_name_youtube'=>'Nome aplicativo API',
			'token_api_youtube'=>'Token API Credencial',
			'meta_key_1'=>'Meta key do vídeo 1',
			'meta_key_2'=>'Meta key do vídeo 2',
			'post_type'=>$p_types,
			'id_canal'=>'ID do canal para cadastrar os videos <br><small style="color: #f00;">*traz somente 45 por mês</small>',
			'ano_inicio'=>'Ano do primeiro vídeo <br><small style="color: #f00;">(exemplo: 2012) *se 0=ano atual</small>'
		);

		$fields = $this->fields;

		include "templates/home.php";
	}

	public function get_videos_wp(){

		$class_this = new GetDetailYoutube;

		$meta_key_1 = get_option('meta_key_1');

		$meta_key_2 = get_option('meta_key_2');

		$post_types = get_option('post_type');

		$videos = get_posts(array('post_type'=>$post_types, 'posts_per_page'=>-1, 'order'=>'DESC', 'orderby'=>'date'));
		
		$ids_videos = array();

		foreach ($videos as $key => $video) {

			$video_meta = get_post_meta($video->ID, $meta_key_2, true);
			if(empty($video_meta)){
				$video_meta = get_post_meta($video->ID, $meta_key_1, true);
			}

			$ex_video = explode('watch?v=', $video_meta);

			if(empty($ex_video[1])){
				$ex_video = explode('youtu.be/', $video_meta);
				$ex_var = explode('?',$ex_video[1]);
				if(!empty($ex_var[1])){
					$ex_video[1] = $ex_var[0];
				}
			}

			$ids_videos[$ex_video[1]] = $video->ID;

			$ids_youtube[] = $ex_video[1];
		}

		if(count($ids_youtube)>45){

			$limit_api = 45;

			$ids_youtube_limit = array();

			for ($v=0; $v <= count($ids_youtube); $v++) {
				
				$ids_youtube_limit[] = $ids_youtube[$v];

				if($v==$limit_api){

					$class_this->apiyt($ids_youtube_limit, $ids_videos);
					
					$ids_youtube_limit = array();

					$limit_api = $limit_api+45;

					sleep(1);
				}
			}

		}else{
			$class_this->apiyt($ids_youtube, $ids_videos);
		}
	}

	public function apiyt($ids_youtube, $ids_videos){

		/**
		 * Sample PHP code for youtube.videos.list
		 * See instructions for running these code samples locally:
		 * https://developers.google.com/explorer-help/guides/code_samples#php
		 */

		if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
		throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
		}
		require_once __DIR__ . '/vendor/autoload.php';
		$client = new Google_Client();
		$client->setApplicationName(get_option('app_name_youtube'));
		$client->setDeveloperKey(get_option('token_api_youtube'));

		// Define service object for making API requests.
		$service = new Google_Service_YouTube($client);

		$queryParams = [
			'id' => implode(',', $ids_youtube)
		];
		
		$response = $service->videos->listVideos('snippet,statistics', $queryParams);
		
		foreach ($response['items'] as $key_final => $youtube) {

			$data_youtube = $youtube->snippet->publishedAt;
			$visualizacoes_youtube = $youtube->statistics->viewCount;
			$likes_youtube = $youtube->statistics->likeCount;
			$deslikes_youtube = $youtube->statistics->dislikeCount;
			$comentarios_youtube = $youtube->statistics->commentCount;

			$video_description = $youtube->snippet->description;
			$video_letra = explode("LETRA:", $video_description);
			$video_letra = explode("#",$video_letra[1]);
			$video_description = $video_letra[0];

			$video_main = $ids_videos[$youtube->id];

			if(!empty($video_main)){
				
				delete_post_meta($video_main, 'data_youtube');
				add_post_meta($video_main, 'data_youtube', $data_youtube);

				delete_post_meta($video_main, 'visualizacoes_youtube');
				add_post_meta($video_main, 'visualizacoes_youtube', $visualizacoes_youtube);

				delete_post_meta($video_main, 'likes_youtube');
				add_post_meta($video_main, 'likes_youtube', $likes_youtube);

				delete_post_meta($video_main, 'deslikes_youtube');
				add_post_meta($video_main, 'deslikes_youtube', $deslikes_youtube);

				delete_post_meta($video_main, 'comentarios_youtube');
				add_post_meta($video_main, 'comentarios_youtube', $comentarios_youtube);

				delete_post_meta($video_main, 'letra_video');
				delete_post_meta($video_main, 'letra_da_musica');
				add_post_meta($video_main, "letra_da_musica", nl2br($video_description));

				$tags = $youtube->snippet->tags;
				/*foreach($tags as $tag){

					$artista_name = explode("mc ", strtolower($tag));
					$prefixo = "MC";

					if(empty($artista_name[1])){
						$artista_name = explode("dj ", strtolower($tag));
						$prefixo = "DJ";
					}

					if(!empty($artista_name[1])){
						$artista_name = $artista_name[1];
						// $artista_name = $artista_name[0];

						$args = array(
							'taxonomy'      => array( 'artistas' ),
							'orderby'       => 'id', 
							'order'         => 'ASC',
							'hide_empty'    => false,
							'fields'        => 'all',
							'name__like'    => $artista_name
						); 
						$terms = get_terms( $args );

						if(empty($terms)){
							$term_id = wp_insert_term( ucfirst($artista_name), 'artistas');
							$term = get_term($term_id);
							wp_remove_object_terms($video_main, array($term_id), 'artistas');
							wp_set_post_terms( $video_main, $term_id, 'artistas', true );
						}else{
							$term = $terms[0];
							wp_remove_object_terms($video_main, array($term->term_id), 'artistas');
							wp_set_post_terms( $video_main, $term->term_id, 'artistas', true );
						}
						if(!empty($term)){
							// delete_term_meta($term->term_id, 'prefixo');
							// add_term_meta($term->term_id, 'prefixo', $prefixo);
							// update_field( 'prefixo', $prefixo, 'artistas_'.$term->term_id );
						}
						sleep(1);
					}

					$term=null;
				}*/
			}

			$video_main = null;
		}

		// echo (json_encode($response['items']));
		// die();

	}

	public function get_anos_videos(){

		$class_this = new GetDetailYoutube;

		$ano_inicio = get_option('ano_inicio');

		if($ano_inicio==0){
			$ano_inicio = date('Y');
		}

		$ano_fim = date('Y');
		for ($y=$ano_inicio; $y <= $ano_fim; $y++) {
			for ($m=1; $m <= 12; $m++) {

				$after = $y.'-'.$m.'-01T00:00:00Z';
				$before = $y.'-'.($m+1).'-01T00:00:00Z';
				
				// echo $after;
				// echo '<br>';
				// echo $before; 
				// echo '<br>';
				// echo '<br>';
				if($m==12){
					$after = $y.'-'.$m.'-01T00:00:00Z';
					$before = ($y+1).'-01-01T00:00:00Z';
				}

				$class_this->get_videos_yt($after,$before);
				sleep(120);
			}
		}
		$class_this->get_videos_wp();
		die();
	}

	public function get_videos_yt($after, $before){

		$post_types = get_option('post_type');

		$meta_key_1 = get_option('meta_key_1');

		/**
		 * Sample PHP code for youtube.videos.list
		 * See instructions for running these code samples locally:
		 * https://developers.google.com/explorer-help/guides/code_samples#php
		 */

		if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
		throw new Exception(sprintf('Please run "composer require google/apiclient:~2.0" in "%s"', __DIR__));
		}
		require_once __DIR__ . '/vendor/autoload.php';
		$client = new Google_Client();
		$client->setApplicationName(get_option('app_name_youtube'));
		$client->setDeveloperKey(get_option('token_api_youtube'));
	
		// Define service object for making API requests.
		$service = new Google_Service_YouTube($client);
	
		$queryParams = [
			'channelId' => get_option('id_canal'),
			'maxResults' => 45,
			'order' => 'date',
			'publishedAfter' => $after,
			'publishedBefore' => $before,
			'type'=>'video'
		];

		$response = $service->search->listSearch('snippet,id', $queryParams);

		if(!empty($response['items'])){
			foreach ($response['items'] as $key_final => $youtube) {

				$video_id = $youtube->id->videoId;
				$video_title = $youtube->snippet->title;

				$video_description = $youtube->snippet->description;
				$video_letra = explode("LETRA:", $video_description);
				$video_letra = explode("#",$video_letra[1]);
				$video_description = $video_letra[0];
				
				$url_video = 'https://www.youtube.com/watch?v='.$video_id;
				$args = array(
					'post_type'=>$post_types,
					'posts_per_page'=>1,
					'meta_query' => array(
						array(
							'key' => $meta_key_1,
							'value' => $video_id,
							'compare' => 'LIKE'
						)
					)
				 );
				$verifica_exist = get_posts($args);
				if(empty($verifica_exist)){
					
					$my_video = array(
						'post_type' => $post_types,
						'post_title' => wp_strip_all_tags( $video_title ),
						'post_status' => 'publish'
					);
					   
					// Insert the post into the database
					$id_video = wp_insert_post( $my_video );

					add_post_meta($id_video, $meta_key_1, $url_video);

					delete_post_meta($id_video, 'letra_video');
					delete_post_meta($id_video, 'letra_da_musica');
					add_post_meta($id_video, "letra_da_musica", nl2br($video_description));
				}else{
					if(empty(get_post_meta($verifica_exist[0]->ID, 'letra_video', true))){
						delete_post_meta($id_video, 'letra_video');
						delete_post_meta($id_video, 'letra_da_musica');
						add_post_meta($id_video, "letra_da_musica", nl2br($video_description));
					}
				}
			}
		}
	}

}

$init_plugin = new GetDetailYoutube;

?>
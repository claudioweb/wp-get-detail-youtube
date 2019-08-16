<div class="wrap">
	<h2><?php echo $this->name_plugin; ?></h2>
	<p>Este plugin ira coletar informações gerais dos videos.</p>

	<p>Adicionar a ACTION <b><code>get_detail_youtube</code></b> para CRON ou fixo <code>do_action('get_detail_youtube');</code>

	<form action="<?php echo admin_url(); ?>" method="POST" name="<?php echo sanitize_title($this->name_plugin); ?>">
		<table class="form-table">
			<tbody>
				
				<?php foreach ($fields as $key_field => $field) : ?>
					<tr>
						<th scope="row">
							<?php if(count($field)>1): ?>
								<label><?php echo $key_field; ?></label>
							<?php else: ?>
								<label><?php echo $field; ?></label>
							<?php endif; ?>
						</th>
						<td>
							<?php if($key_field=='meta_key_1' || $key_field=='meta_key_2' || $key_field=='token_api_youtube' || $key_field=='app_name_youtube'): ?>

								<input name="<?php echo $key_field; ?>" type="text" id="<?php echo $key_field; ?>" value="<?php echo get_option($key_field); ?>" class="regular-text">
							<?php elseif($key_field=='ano_inicio'): ?>
								<input name="<?php echo $key_field; ?>" type="number" id="<?php echo $key_field; ?>" value="<?php echo get_option($key_field); ?>" class="regular-text">
							<?php elseif($key_field=='id_canal'): ?>
								<input name="<?php echo $key_field; ?>" type="text" id="<?php echo $key_field; ?>" value="<?php echo get_option($key_field); ?>" class="regular-text">
							<?php else: ?>

								<select id="<?php echo $key_field; ?>" <?php if($key_field=='post_type'){ /*echo 'multiple ';  echo 'name="'.$key_field.'[]"'; */echo 'name="'.$key_field.'"'; }else{  echo 'name="'.$key_field.'"'; } ?>>
									
									<?php foreach($field as $fo): ?>
										<option value="<?php echo $fo; ?>" <?php if(get_option($key_field)==$fo || in_array($fo, get_option($key_field))) { echo 'selected'; } ?>><?php echo $fo; ?></option>
									<?php endforeach; ?>

								</select>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>

				<tr>
					<td>
						<p class="submit">
							<input type="submit" name="salvar" id="salvar" class="button button-primary" value="Salvar alterações">
						</p>
					</td>
				</tr>
			</tbody>
		</table>
	</form>

</div>
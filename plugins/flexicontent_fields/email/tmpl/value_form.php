<?php
JHtml::_('behavior.formvalidator');
JHtml::_('behavior.modal', 'a.modal');

// Create field's HTML
$field->{$prop} = array();
$n = 0;

foreach ($values as $value)
{

	// Basic sanity check for a valid email address
	$value['addr'] = !empty($value['addr']) && strpos($value['addr'], '@') !== false ? $value['addr'] : '';

	// Skip empty value, adding an empty placeholder if field inside in field group
	if ( empty($value['addr']) )
	{
		if ( $is_ingroup )
		{
			$field->{$prop}[$n++]	= '';
		}
		continue;
	}

	// If not using property or property is empty, then use default property value
	// NOTE: default property values have been cleared, if (propertyname_usage != 2)
	$addr = $value['addr'];
	$text = @$value['text'];
	$text = ($usetitle && strlen($text))  ?  $text  :  $default_title;

	if ( !strlen($text) || !$usetitle )
	{
		$text = JStringPunycode::emailToUTF8($addr);  // email in Punycode to UTF8, for the purpose of displaying it
		$text_is_email = 1;
	}
	else
	{
		$text_is_email = strpos($text,'@') !== false;
	}

	// Create field's display
	$submit_class = $field->parameters->get('submit_class', 'btn');
	$label_position = $field->parameters->get('label_position', '');

	// Label position
	switch ($label_position)
	{
		case 'top': $class = 'label-top'; break;
		case 'placeholder': $class = 'placeholder'; break;
		default: $class=''; break;
	}

	// Add styles for label position
	$document = JFactory::getDocument();
	$styleurl = JUri::root(true) . '/plugins/flexicontent_fields/email/css/style.css';
	$document->addStyleSheet($styleurl);

	// Use paremeters to decide if email should be cloaked and if we need a mailto: link

	// Title form display
	$titleform         = JText::_($field->parameters->get('title_form', ''));
	$display_titleform = $field->parameters->get('display_title_form', 0);

	$titleformD = $display_titleform ? '<LEGEND>'.$titleform.'</LEGEND>' : '';

	//Fake id form, cutt email on @ and set startemail
	$formid =  explode('@', $addr);
	$formid = $formid[0] .'_'. random_int(100, 999); //add a random id if we have 2 forms in same page but with same email author

	// Modal display 
	// TODO replace joomla modal for flexicontent modal base on jquery
	$view = $app->input->getCmd('flexi_callview', ($realview ?: 'item'));
	$use_modal = $field->parameters->get('use_modal', 1);
	$use_modal_in_view = $field->parameters->get('use_modal_in_view', 'both');
	$modal_button_text = $field->parameters->get('modal_button_text', 'FLEXI_FIELD_EMAIL_MODAL_BUTTON_CONTENT');
	$modal_button_class = $field->parameters->get('modal_button_class', 'btn btn-info');
	$modal_height = $field->parameters->get('modal_height', 400);
	$modal_width = $field->parameters->get('modal_width', 400);

	if (
		($use_modal == 1 && $view=='item' && $use_modal_in_view =='item') ||
		($use_modal == 1 && $view=='category' && $use_modal_in_view =='category') ||
		($use_modal == 1 && $use_modal_in_view =='both')
	)
	{
		$modal_header = "
		<button id='modal_info' data-toggle='modal' data-target='#myModal'$formid' class='$modal_button_class' rel=\"{handler: 'popover', size: {x: $modal_height, y: $modal_width}}\" >".Jtext::_($modal_button_text)."</button>
		<div id='myModal'$formid' class='modal hide fade' role='dialog'  tabindex='-1' role='dialog' aria-labelledby='contact' aria-hidden='true'>
			  <div class='modal-header' style='border-bottom: 0px solid #eee;'>
				<button type='button' class='close' data-dismiss='modal'>&times;</button>
			  </div>
			  <div class='modal-body'>";
		$modal_footer = "		
				</div>
				<div class='modal-footer'>
			</div>
		</div>";
	}
	else
	{
		$modal_header = '';
		$modal_footer = '';
	}
	// Consent field
	$consent_field_display = (int) $field->parameters->get('display_consent', 1);
	$consent_field_text    = $field->parameters->get('text_consent', 'FLEXI_FIELD_EMAIL_CONSENT_LABEL_VALUE');
	$consent_field_link    = $field->parameters->get('link_consent', '');
	$consent_field = '';
	if ($consent_field_display)
	{
		$consent_field = '
			<div class="field form-group control-group">
				<input type="checkbox" id="consent" name="consent" value="consent" class="required">
				<label for="consent">
				<a href="'.$consent_field_link.'" target="_blank">'.Jtext::_($consent_field_text).'</a>
				</label>
			</div>
		';
	}
	//Captcha
	$display_captcha = (int) $field->parameters->get('display_captcha', 0);
	$captcha_plgname = $display_captcha ? $app->getCfg('captcha') : '0';
	$captcha_html    = '';
	if ($captcha_plgname)
	{
		JPluginHelper::importPlugin('captcha');
		$dispatcher = JEventDispatcher::getInstance();
		// This will put the code to load reCAPTCHA's JavaScript file into your <head>
		$results = FLEXI_J40GE
			? $app->triggerEvent('onInit', array('dynamic_recaptcha_1'))
			: $dispatcher->trigger('onInit', array('dynamic_recaptcha_1'));
		// This will return the array of HTML code.
		$recaptcha = $dispatcher->trigger('onDisplay', array(null, 'dynamic_recaptcha_1', 'class="required"'));
		if (!empty($recaptcha[0]))
		{
			$captcha_html= '<div class="captcha form-group control-group">'.$recaptcha[0].'</div>';
		}
	}
	$fields_display = '';
	$list_fields    = $field->parameters->get('form_fields');
	if ($list_fields)
	{
		foreach ($list_fields as $list_fields_idx => $list_field)
		{
			//print_r ($list_field);
			// Check and create required class
			$required = $list_field->field_required ? 'required' : '';
			// Create JText value
			$field_label = JText::_($list_field->field_label);
			// Create field id
			$field_id = preg_replace("#[^a-zA-Z-0-9_]#", "", JText::_($list_field->field_name));

			// Create field name
			$field_name = $formid.'['.JText::_($list_field->field_name).']';

			// Create field value
			$field_value = preg_replace("#[^a-zA-Z-0-9]#", "", JText::_($list_field->field_value));

			// Placeholder
			$placeholder = ($label_position === 'placeholder') ? ' placeholder="' . $list_field->field_label . '"' : '';

			if ($list_field->field_type === 'text')
			{
				$fields_display .= '<div class="'.$field_id.' field field_text form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="text" name="'.$field_name.'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.'" style="margin:0"></div>';
			}

			if ($list_field->field_type === 'email')
			{
				$fields_display .= '<div class="'.$field_id.' field field_email form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="email" name="'.$field_name.'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.' validate-email" style="margin:0"></div>';
			}

			if ($list_field->field_type === 'date')
			{
				$fields_display .= '<div class="'.$field_id.' field field_date form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="date" name="'.$field_name.'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.'" style="margin:0"></div>';
			}

			if ($list_field->field_type === 'datetime-local')
			{
				$fields_display .= '<div class="'.$field_id.' field field_datetime form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="datetime-local" name="'.$field_name.'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.'" style="margin:0"></div>';
			}

			if ($list_field->field_type === 'textarea')
			{
				$fields_display .= '
					<div class="' . $field_id . ' field field_textarea form-group control-group">
						<label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label>
						<textarea rows="4" cols="50" name="'.$field_name.'" '.$placeholder.' aria-label="'.$field_label.'"
							id="'.$field_id.'" class="form-control '.$required.'" style="width: 100%; margin: 0;">
						</textarea>
					</div>';
			}

			if ($list_field->field_type === 'radio')
			{
				$values_field = explode(";;",$list_field->field_value);
				$fields_display .= '<div class="'.$field_id.' field field_radio form-group control-group"><label for="'.$field_id.'" class="'.$class.'-radio">'.$list_field->field_label.'</label>';

				foreach ($values_field as $value_field)
				{
					$value =  JText::_($value_field);
					$fields_display .= '<input type="radio" value="'.$value.'" name="'.$formid.'['.$value.']'.'" aria-label="'.$value.'" style="margin:0" class="form-control"><label for="'.$field_id.'">'.$value.'</label>';
				}

				$fields_display .='</div>';
			}

			if ($list_field->field_type === 'checkbox')
			{
				$values_field = explode(";;",$list_field->field_value);
				$fields_display .= '<div class="'.$field_id.' field field_checkbox form-group control-group"><label for="'.$field_id.'" class="'.$class.'-checkbox">'.$list_field->field_label.'</label>';

				foreach ($values_field as $value_field)
				{
					$value = JText::_($value_field);
					$fields_display .= '<input type="checkbox" value="'.$value.'" name="'.$value.'" class="form-control" aria-label="'.$value.'" style="margin:0" ><label for="'.$field_id.'">'.$value.'</label>'; //TODO add required system
				}
				$fields_display .='</div>';
			}

			if ($list_field->field_type === 'select')
			{
				$values_field = explode(";;",$list_field->field_value);
				$select_label = ($class == 'placeholder') ? $field_label : JText::_('FLEXI_SELECT');
				$fields_display .= '<div class="'.$field_id.' field field_select form-group control-group" ><label for="'.$field_id.'" class="'.$class.'" style="margin:0">'.$field_label.'</label><select id="'.$field_name.'" name="'.$field_name.'" aria-label="'.$field_label.'" class="form-control"><option value="">'.$select_label.'</option>';//TODO add required system

				foreach ($values_field as $value_field)
				{
					$value = JText::_($value_field);
					$fields_display .='<option value="'.$value.'">'.JText::_($value_field).'</option>';
				}
				$fields_display .='</select></div>';
			}

			if ($list_field->field_type === 'file')
			{
				$values_field = explode(";;",$list_field->field_value);

				if (!empty($values_field[1]) && $values_field[1] === 'multiple')
				{
					$uploadmode = 'multiple';
					$maxupload  = 'data-max="'.$values_field[2].'"';
				}
				else
				{
					$uploadmode='';
					$maxupload  ='';
				}

				// Placehoder is already printed because needed for js alert
				$fields_display .= '<div class="'.$field_id.' field field_file form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="file" name="'.$field_name.'[]" accept="'.$values_field[0].'" id="'.$field_id.'" placeholder="'.$list_field->field_label.'" aria-label="'.$field_label.'" class="inputfile '.$required.'" '.$uploadmode.' style="margin:0"  '.$maxupload.' ></div>';
			}

			if ($list_field->field_type === 'phone')
			{
				$fields_display .= '<div class="'.$field_id.' field field_phone form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="phone" name="'.$field_name.'" pattern="'.$value.'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.'" style="margin:0"></div>';
			}

			if ($list_field->field_type === 'hidden')
			{
				$fields_display .= '<input type="hidden" name="'.$field_name.'" id="'.$field_id.'" value="'.$field_value.'">';
			}

			if ($list_field->field_type === 'freehtml')
			{
				$fields_display .= '<div class="'.$field_id.' field field_html form-group control-group"><p>'.$field_label.'</p><p>'.JText::_($list_field->field_value).'</p></div>';
			}

			if ($list_field->field_type === 'url')
			{
				$fields_display .= '<div class="'.$field_id.' field field_url form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="url" name="'.$field_name.'" pattern="'.$list_field->field_value.'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.'" style="margin:0"></div>';
			}

			if ($list_field->field_type === 'range')
			{
				$values_field = explode(';;', $list_field->field_value);
				$fields_display .= '<div class="'.$field_id.' field field_range form-group control-group"><label for="'.$field_id.'" class="'.$class.'">'.$field_label.'</label><input type="range" name="'.$field_name.'" min="'.$values_field[0].'" max="'.$values_field[1].'" step="'.$values_field[2].'" id="'.$field_id.'" '.$placeholder.' aria-label="'.$field_label.'" class="form-control '.$required.'" style="margin:0"></div>';
			}
		}
	}

	$html = '
	'.$modal_header.'
		<form id="contact-form-'.$formid.'" method="POST" class="form-validate" enctype="multipart/form-data">
		<fieldset>
			'.$titleformD.'
			'.$fields_display.'
			'.$consent_field.'
			'.$captcha_html.'
		<div class="form-group control-group submit-button">
		<input type="submit" name="submit" value="'.JText::_('FLEXI_FIELD_EMAIL_SUBMIT_LABEL_VALUE').'" class="'.$submit_class.'">
		</div>
		<input type="hidden" name="emailtask" value="plg.email.submit" />
		<input type="hidden" name="formid" value="'.$formid.'" />
		<input type="hidden" name="emailauthor" value="'.$addr.'" />
		<input type="hidden" name="itemid" value="'.$item->id.'" />
		<input type="hidden" name="itemtitle" value="'.$item->title.'" />
		<input type="hidden" name="itemalias" value="'.$item->alias.'" />
		<input type="hidden" name="itemauthor" value="'.$item->author.'" />
		<input type="hidden" name="catid" value="'.$item->catid.'" />
		<input type="hidden" name="return" value="" />
		'.JHtml::_("form.token").'
		</fieldset>
		</form>
		'.$modal_footer.'
		<script>
			const qsa=(s,o)=>[...(o||document).querySelectorAll(s)],
				qs=(s,o)=>qsa(s,o)[0];
			qs("input[type=submit]").addEventListener(\'click\',function(e)
			{
				qsa("input[type=\'file\']").forEach(inp=>
				{
					if (inp.files.length > inp.dataset.max)
					{
						alert(`'.JText::_("FLEXI_ALLOWED_NUM_FILES").' ${inp.dataset.max} '.JText::_("FLEXI_FILES_FOR").' ${inp.placeholder}`);
						e.preventDefault();
					}
				});
			});
		</script>
	';

	// Add prefix / suffix
	$field->{$prop}[$n]	= $pretext . $html . $posttext;

	$n++;
	if (!$multiple) break;  // multiple values disabled, break out of the loop, not adding further values even if the exist
}
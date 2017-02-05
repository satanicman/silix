<div id="qform" class="quickform">
	<h2 class="title">{l s='Quick order' mod='quickorder'}</h2>
	<div class="qform_container">
	<div id="errors" class="error hidden"></div>
	<div id="success" class="success hidden">{l s='Thanks' mod='quickorder'}<br>{l s='Order complete' mod='quickorder'}</div>
	{if $total <= 0}<div class="error">{l s='Your cart is is empty' mod='quickorder'}</div>{/if}

	{if $total > 0}
	<div id="wrap" class="form_container">
		<p class="text">
			<label for="firstname">{l s='First name:' mod='quickorder'}<sup class="required">*</sup></label>
			<input type="text" class="required" id="firstname" name="firstname" value="{if $logged}{$cookie->customer_firstname}{/if}">
			
		</p>
		<p class="text">
			<label for="lastname">{l s='Last name:' mod='quickorder'}<sup class="required">*</sup></label>
			<input type="text" class="required" id="lastname" name="lastname" value="{if $logged}{$cookie->customer_lastname}{/if}">
			
		</p>
		<p class="text">
			<label for="email">{l s='E-mail:' mod='quickorder'}<sup class="required">*</sup></label>
			<input type="text" class="required" id="email" name="email" value="{if $logged}{$cookie->email}{/if}" />

		</p>
		<p class="text">
			<label for="phone_mobile">{l s='Mobile phone:' mod='quickorder'}<sup class="required">*</sup></label>
			<input type="text" class="required" name="phone_mobile" id="phone_mobile" value="" />

		</p>
		<p class="text">
			<label for="payment">{l s='Payment:' mod='quickorder'}<sup class="required">*</sup></label>
			<select name="payment" id="payment" class="form-control_small form-control_full">
				<option value="{l s='cash on delivery' mod='quickorder'}">{l s='cash on delivery' mod='quickorder'}</option>
				<option value="{l s='payment on the card' mod='quickorder'}">{l s='payment on the card' mod='quickorder'}</option>
				<option value="{l s='cash on delivery 2' mod='quickorder'}">{l s='cash on delivery 2' mod='quickorder'}</option>
			</select>

		</p>
		<p class="text textarea">
			<label for="address">{l s='Address:' mod='quickorder'}<sup class="required">*</sup></label>
			<textarea name="address" id="address" cols="26" rows="2"></textarea>

		</p>
		<p class="text textarea">
			<label for="comment">{l s='Comment:' mod='quickorder'}</label>
			<textarea name="comment" id="comment" cols="26" rows="5"></textarea>
		</p>
	</div>
	<div class="submit">
        <div class="myrequired"><sup class="required">*</sup> {l s='Required fields' mod='quickorder'}</div>
		<input class="button" type="submit" title="{l s='Click here to submit your order!' mod='quickorder'}" name="submitOrder" id="submitOrder" value="{l s='Submit order' mod='quickorder'}">
	</div>

	{/if}
</div>
</div>
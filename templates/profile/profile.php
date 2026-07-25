<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

$user = wp_get_current_user();

$fields = [
	'first_name',
	'last_name',
	'description',
	'nb_phone',
	'nb_country',
	'nb_state',
	'nb_city',
	'nb_niche',
	'nb_payment_method',
];

$completed = 0;

foreach ($fields as $field) {

	if (! empty(get_user_meta($user->ID, $field, true))) {

		$completed++;

	}

}

$profile_completion = (int) round(
	($completed / count($fields)) * 100
);

$status = get_user_meta(
	$user->ID,
	'nb_account_status',
	true
);

$status = $status ?: __('Pending', 'newsblenda-accounts');

?>

<div class="nba-profile-page">

	<header class="nba-profile-header">

		<h1>

			<?php esc_html_e(
				'My Profile',
				'newsblenda-accounts'
			); ?>

		</h1>

		<p>

			<?php esc_html_e(
				'Manage your Newsblenda account information.',
				'newsblenda-accounts'
			); ?>

		</p>

	</header>

	<div class="nba-dashboard-grid">

		<div class="nba-card">

			<h3><?php esc_html_e('Profile Completion', 'newsblenda-accounts'); ?></h3>

			<div class="nba-card-number">

				<?php echo esc_html($profile_completion . '%'); ?>

			</div>

		</div>

		<div class="nba-card">

			<h3><?php esc_html_e('Account Status', 'newsblenda-accounts'); ?></h3>

			<div class="nba-card-number">

				<?php echo esc_html(ucfirst((string) $status)); ?>

			</div>

		</div>

	</div>

	<?php if (isset($_GET['updated'])) : ?>

		<div class="nba-message nba-message-success">

			<?php esc_html_e(
				'Your profile has been updated successfully.',
				'newsblenda-accounts'
			); ?>

		</div>

	<?php endif; ?>

	<form method="post" class="nba-profile-form">

		<?php wp_nonce_field('nbe_nonce'); ?>

		<input
			type="hidden"
			name="nbe_profile_submit"
			value="1"
		>

		<h2><?php esc_html_e('Basic Information', 'newsblenda-accounts'); ?></h2>

		<div class="nba-form-group">

			<label for="nbe_full_name">

				<?php esc_html_e(
					'Full Name',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_full_name"
				type="text"
				name="nbe_full_name"
				value="<?php echo esc_attr($user->display_name); ?>"
				required
			>

		</div>

		<div class="nba-form-group">

			<label for="nbe_email">

				<?php esc_html_e(
					'Email Address',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_email"
				type="email"
				name="nbe_email"
				value="<?php echo esc_attr($user->user_email); ?>"
				required
			>

		</div>

		<div class="nba-form-group">

			<label for="nbe_biography">

				<?php esc_html_e(
					'Biography',
					'newsblenda-accounts'
				); ?>

			</label>

			<textarea
				id="nbe_biography"
				name="nbe_biography"
				rows="6"
			><?php echo esc_textarea($user->description); ?></textarea>

		</div>
        
        		<hr>

		<h2>

			<?php esc_html_e(
				'Personal Information',
				'newsblenda-accounts'
			); ?>

		</h2>

		<div class="nba-form-group">

			<label for="nb_phone"><?php esc_html_e('Phone', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_phone"
				type="text"
				name="nbe_phone"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_phone', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_whatsapp"><?php esc_html_e('WhatsApp', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_whatsapp"
				type="text"
				name="nbe_whatsapp"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_whatsapp', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_gender"><?php esc_html_e('Gender', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_gender"
				type="text"
				name="nbe_gender"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_gender', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_dob"><?php esc_html_e('Date of Birth', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_dob"
				type="date"
				name="nbe_dob"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_dob', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_country"><?php esc_html_e('Country', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_country"
				type="text"
				name="nbe_country"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_country', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_state"><?php esc_html_e('State', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_state"
				type="text"
				name="nbe_state"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_state', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_city"><?php esc_html_e('City', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_city"
				type="text"
				name="nbe_city"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_city', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_address"><?php esc_html_e('Address', 'newsblenda-accounts'); ?></label>

			<textarea
				id="nbe_address"
				name="nbe_address"
				rows="3"
			><?php echo esc_textarea(get_user_meta($user->ID, 'nb_address', true)); ?></textarea>

		</div>

		<hr>

		<h2>

			<?php esc_html_e(
				'Author Information',
				'newsblenda-accounts'
			); ?>

		</h2>

		<div class="nba-form-group">

			<label for="nb_occupation"><?php esc_html_e('Occupation', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_occupation"
				type="text"
				name="nbe_occupation"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_occupation', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_niche"><?php esc_html_e('Niche', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_niche"
				type="text"
				name="nbe_niche"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_niche', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_experience"><?php esc_html_e('Experience', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_experience"
				type="text"
				name="nbe_experience"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_experience', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_categories"><?php esc_html_e('Categories', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_categories"
				type="text"
				name="nbe_categories"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_categories', true)); ?>"
			>

		</div>

		<hr>

		<h2>

			<?php esc_html_e(
				'Social Media',
				'newsblenda-accounts'
			); ?>

		</h2>

		<div class="nba-form-group">

			<label for="nb_website"><?php esc_html_e('Website', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_website"
				type="url"
				name="nbe_website"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_website', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_facebook"><?php esc_html_e('Facebook', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_facebook"
				type="url"
				name="nbe_facebook"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_facebook', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_twitter"><?php esc_html_e('Twitter/X', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_twitter"
				type="url"
				name="nbe_twitter"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_twitter', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_instagram"><?php esc_html_e('Instagram', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_instagram"
				type="url"
				name="nbe_instagram"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_instagram', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_linkedin"><?php esc_html_e('LinkedIn', 'newsblenda-accounts'); ?></label>

			<input
				id="nbe_linkedin"
				type="url"
				name="nbe_linkedin"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_linkedin', true)); ?>"
			>

		</div>
        
        		<hr>

		<h2>

			<?php esc_html_e(
				'Payment Information',
				'newsblenda-accounts'
			); ?>

		</h2>

		<div class="nba-form-group">

			<label for="nb_payment_method">

				<?php esc_html_e(
					'Payment Method',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_payment_method"
				type="text"
				name="nbe_payment_method"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_payment_method', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_bank_name">

				<?php esc_html_e(
					'Bank Name',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_bank_name"
				type="text"
				name="nbe_bank_name"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_bank_name', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_account_name">

				<?php esc_html_e(
					'Account Name',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_account_name"
				type="text"
				name="nbe_account_name"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_account_name', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_account_number">

				<?php esc_html_e(
					'Account Number',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_account_number"
				type="text"
				name="nbe_account_number"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_account_number', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_paypal">

				<?php esc_html_e(
					'PayPal Email',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_paypal"
				type="email"
				name="nbe_paypal"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_paypal', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_opay">

				<?php esc_html_e(
					'OPay Account',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_opay"
				type="text"
				name="nbe_opay"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_opay', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_palmpay">

				<?php esc_html_e(
					'PalmPay Account',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_palmpay"
				type="text"
				name="nbe_palmpay"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_palmpay', true)); ?>"
			>

		</div>

		<div class="nba-form-group">

			<label for="nb_moniepoint">

				<?php esc_html_e(
					'Moniepoint Account',
					'newsblenda-accounts'
				); ?>

			</label>

			<input
				id="nbe_moniepoint"
				type="text"
				name="nbe_moniepoint"
				value="<?php echo esc_attr(get_user_meta($user->ID, 'nb_moniepoint', true)); ?>"
			>

		</div>

		<hr>

		<div class="nba-form-actions">

			<button
				type="submit"
				class="button button-primary button-large"
			>

				<?php esc_html_e(
					'Save Profile',
					'newsblenda-accounts'
				); ?>

			</button>

		</div>

	</form>

	<section class="nba-profile-footer">

		<div class="nba-card">

			<h2>

				<?php esc_html_e(
					'Profile Tips',
					'newsblenda-accounts'
				); ?>

			</h2>

			<ul>

				<li>

					<?php esc_html_e(
						'Complete every section to improve your profile completion score.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Keep your payment information accurate to avoid payout delays.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Maintain an up-to-date biography and contact information.',
						'newsblenda-accounts'
					); ?>

				</li>

				<li>

					<?php esc_html_e(
						'Your profile information helps editors communicate with you efficiently.',
						'newsblenda-accounts'
					); ?>

				</li>

			</ul>

		</div>

	</section>

	<?php
	/**
	 * Fires after the profile form.
	 *
	 * Developers can use this hook to add
	 * custom profile sections or fields.
	 */
	do_action(
		'nb_accounts_profile_footer',
		$user
	);
	?>

</div>
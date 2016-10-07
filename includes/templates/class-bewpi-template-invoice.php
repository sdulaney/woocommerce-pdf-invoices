<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'BEWPI_Template_Invoice' ) ) :

/**
 * Customer Invoice.
 *
 * An email sent to the customer.
 *
 * @class       BEWPI_Template_Invoice
 * @version     1.0.0
 * @package     BE_WooCommerce/Classes/Templates
 * @author      Bas Elbers
 * @extends     BEWPI_Template_Invoice
 */
class BEWPI_Template_Invoice extends BEWPI_Template {

	/**
	 * Strings to find in subjects/headings.
	 *
	 * @var array
	 */
	public $find;

	/**
	 * Strings to replace in subjects/headings.
	 *
	 * @var array
	 */
	public $replace;

	/**
	 * Constructor.
	 */
	public function __construct() {

		$this->id             = 'invoice';
		$this->title          = __( 'Invoice', 'woocommerce-pdf-invoices' );
		$this->description    = __( 'Invoice can be generated  invoice emails can be sent to customers containing their order information and payment links.', 'woocommerce' );

		$this->template_html  = 'emails/customer-invoice.php';
		$this->template_plain = 'emails/plain/customer-invoice.php';

		$this->subject        = __( 'Invoice for order {order_number} from {order_date}', 'woocommerce');
		$this->heading        = __( 'Invoice for order {order_number}', 'woocommerce');

		$this->subject_paid   = __( 'Your {site_title} order from {order_date}', 'woocommerce');
		$this->heading_paid   = __( 'Order {order_number} details', 'woocommerce');

		// Call parent constructor
		parent::__construct();

		$this->customer_email = true;
		$this->manual         = true;
		$this->heading_paid   = $this->get_option( 'heading_paid', $this->heading_paid );
		$this->subject_paid   = $this->get_option( 'subject_paid', $this->subject_paid );
	}

	/**
	 * Trigger.
	 *
	 * @param int|WC_Order $order
	 */
	public function trigger( $order ) {

		if ( ! is_object( $order ) ) {
			$order = wc_get_order( absint( $order ) );
		}

		if ( $order ) {
			$this->object                  = $order;
			$this->recipient               = $this->object->billing_email;

			$this->find['order-date']      = '{order_date}';
			$this->find['order-number']    = '{order_number}';

			$this->replace['order-date']   = date_i18n( wc_date_format(), strtotime( $this->object->order_date ) );
			$this->replace['order-number'] = $this->object->get_order_number();
		}

		if ( ! $this->get_recipient() ) {
			return;
		}

		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
	}

	/**
	 * Get email subject.
	 *
	 * @access public
	 * @return string
	 */
	public function get_subject() {
		if ( $this->object->has_status( array( 'processing', 'completed' ) ) ) {
			return apply_filters( 'woocommerce_email_subject_customer_invoice_paid', $this->format_string( $this->subject_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_subject_customer_invoice', $this->format_string( $this->subject ), $this->object );
		}
	}

	/**
	 * Get email heading.
	 *
	 * @access public
	 * @return string
	 */
	public function get_heading() {
		if ( $this->object->has_status( array( 'completed', 'processing' ) ) ) {
			return apply_filters( 'woocommerce_email_heading_customer_invoice_paid', $this->format_string( $this->heading_paid ), $this->object );
		} else {
			return apply_filters( 'woocommerce_email_heading_customer_invoice', $this->format_string( $this->heading ), $this->object );
		}
	}

	/**
	 * Get content html.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'			=> $this
		) );
	}

	/**
	 * Get content plain.
	 *
	 * @access public
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'			=> $this
		) );
	}

	/**
	 * Initialise settings form fields.
	 */
	public function init_form_fields() {
		// get all emails
		$emails = WC()->mailer()->get_emails();
		$email_options = array();
		foreach($emails as $email){
			$email_options[$email->id] = $email->title;
		}

		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'woocommerce-pdf-invoices' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable invoice generation', 'woocommerce-pdf-invoices' ),
				'default'       => 'yes'
			),
			'emails' => array(
				'title'             => __( 'Attach to Emails', 'woocommerce-pdf-invoices' ),
				'type'              => 'multiselect',
				'class'             => 'wc-enhanced-select',
				'css'               => 'width: 450px;',
				'default'           => '',
				'options'           => $email_options,
				'custom_attributes' => array(
					'data-placeholder'  => __( 'Select some emails', 'woocommerce-pdf-invoices' )
				)
			),
			'html' => array(
				'title'             => '',
				'type'              => 'tinymce',
				'disabled'          => false,
				'class'             => '',
				'css'               => '',
				'placeholder'       => '',
				'desc_tip'          => false,
				'description'       => '',
				'custom_attributes' => array(),
			)
		);
	}
}

endif;

return new BEWPI_Template_Invoice();
<?php


namespace Mailgun_Subscriptions;


abstract class Template {
	const CONFIRMATION_EMAIL = "Thank you for subscribing. Please visit [link] to confirm your subscription for [email] to the following lists:\n\n[lists]";
	const WELCOME_EMAIL = "Your email address, [email], has been confirmed. You are now subscribed to the following lists:\n\n[lists]";
}
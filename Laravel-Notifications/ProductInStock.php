<?php

namespace App\Product\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Customer\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Product\Models\StockAlert;
use App\Shared\Support\MoneyFormat;
use App\Product\Mail\StockAlertMail;
use NotificationChannels\Messagebird\MessagebirdChannel;
use NotificationChannels\Messagebird\MessagebirdMessage;

class ProductInStock extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Class constructor.
     *
     * @param StockAlert $stockAlert The stock alert object.
     */
    public function __construct(public StockAlert $stockAlert)
    {
    }

    /**
     * Generate the function comment for the given function body.
     *
     * @return array|string
     */
    public function via(): array|string
    {
        if ($this->stockAlert->alert_type === 'email') {
            return ['mail'];
        } elseif ($this->stockAlert->alert_type === 'sms') {
            return MessagebirdChannel::class;
        }
        return [];
    }

    /**
     * Convert the StockAlertNotification to a Mail message.
     *
     * @param  Customer  $notifiable
     * @return StockAlertMail
     */
    public function toMail(Customer $notifiable): StockAlertMail
    {
        // Create a new StockAlertMail instance
        $mail = new StockAlertMail($this->stockAlert);

        // Set the recipient of the email
        $mail->to($this->stockAlert->alert_to);

        // Return the StockAlertMail instance
        return $mail;
    }

    /**
     * Convert the stock alert notification to a Messagebird message.
     *
     * @param Customer $notifiable The customer who will receive the notification.
     * @return MessagebirdMessage The converted Messagebird message.
     */
    public function toMessagebird(Customer $notifiable): MessagebirdMessage
    {
        // Get the product associated with the stock alert.
        $product = $this->stockAlert->product;

        // Get the metal properties of the product.
        $metalProperties = $product->metalProperties;

        // Format the metal properties into a human-readable price string.
        $price = MoneyFormat::metalProperties($metalProperties);

        // Create the stock alert notification message.
        $message = sprintf("%s is back in stock and costs %s.", $product->name, $price);

        // Create a new Messagebird message and set the recipients.
        $messagebirdMessage = new MessagebirdMessage($message);
        $messagebirdMessage->setRecipients($this->stockAlert->alert_to);

        // Return the converted Messagebird message.
        return $messagebirdMessage;
    }
}

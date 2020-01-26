# resultkitchen

Are you running a long sales cycle B2B business, or an ecommerce business that gets a significant amounnt of revenue over the phone?

Then you are likely leaving alot of money on the table.
If you aren't feeding revenue numbers at the click level to your Google Analytics, Adwords, Bing & Facebook, you're missing the opportunity to optimize towards
actual revenue. At best, you're optimizing towards phone calls. But not every phone call and website chat is equal.


This script has been released to help advertisers to connect their ERP/CRM/Shopping cart to Call Tracking Metrics and Google Analytics.
This solves a significant challenge advertisers who have alot of their revenue coming in over the phone.

We prefer call tracking metrics over other solutions, as they are the only one we know that uses an algorithm to predict which click to call / call extension clicks
have turned into revenue. 

Call Tracking Metrics DOES have the ability to post revenue back to Google Analytics, Adwords & Bing.

However, what they don't support is providing detailed product level data to Google Analytics, which removes your ability to 
understand the merchandising aspect.

Further, while the API is open to post revenue back to them, you still need to post your data back from your CRM and create the API posts.

This script does that coding, but also allows you to post the order back to Google Analytics with all of the product order information.

The general process is as follows:

1)The following fields are in our CSV file:

Date
Phone Caller ID
Order Number
Name
SKU
Quantity
Rate
Amount
SubTotal
Discount
Tax
Shipping
Cost
Total Cost (qty * Cost)
Category
Shipping Phone
Billing Phone
Line Sequence Number  

// (for each order with multiple SKU's, Line Sequence number would show which number row it is)

These orders should be the days orders	

## Installation

### 1 Download files
### 2 Run `composer update`
### 3 Edit  update_ga.php and update_ctm.php settings (top of file)
### 	- Add in the correct GA code, Call Tracking Metrics Account Number and API key, and OrdersURL - the URL of your order file
### 4 Setup cron to call update_ga.php and update_ctm.php every day

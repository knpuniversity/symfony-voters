Hey guys! It's getting a little colder in Michigan, Leanna and I are doing a 
little bit of baking, baking makes me think of security. Specifically the kind 
of security that says: "no you can't eat my cookie because I baked it" and it's 
actually has applications inside of her application because a lot of times we 
need to figure out whether the current user has access to edit, delete or view 
something. It's because of this we've cooked up a delicious little application 
which is going to show you one of my favorite and most underutilized features 
in Symfony: security voters. 

So I'll login using username Ryan password cookie and basically we only have 
one page in this app which shows us all of these cookies that Ryan and Leanna have 
baked. Each of those has a nom button which allows me to eat the cookie shows 
me a nice message and deletes it from the database. Really high-tech stuff. The 
application is pretty straight forward, we have an app bundle of course and 
inside of there we have a single entity called `DeliciousCookie`. The most 
important thing about `DeliciousCookie` is that there's a `baker_username` which 
stores who actually baked this cookie. 

To keep this application simple I don't have a user entity so I'm just using a 
username string there. right now anybody can eat any cookie no matter who 
baked it. But our goal is to make it so that you can only eat cookies that 
you've baked. The cookie controller holds the page that actually lists the 
cookies and then there's one post only end point which handles actually 
deleting the cookie in the database and setting that nice flash message. 

The only other interesting thing is security.yml. We have two hard coded users, 
Ryan and Leanna, and I also have an access control which requires login for 
everything under /cookies which is why we had to login before we saw our cookie 
list. We take cookie security very seriously. Preventing me from eating a cookie 
baked by someone else is actually pretty simple. And what we should do first is 
just put the logic into our controller.

So I'll do that here, if the baker's username does not equal the current user's 
username we're going to throw that access denied exception and say: "Hey you 
didn't bake this!" Now if we try to eat one of Leanna's cookies she catches us 
with a nice clear message and of course in the production environment this would 
be your 403 error page. 

So what's the problem with this? The problem is that we also need to go into 
our twig template and repeat the logic there. And when it comes to security 
logic, especially security logic that protects cookies, we don't want to 
repeate it across your application. If you change something later and forget 
to update part of your security you're going to have a big problem. But for now 
I'll do it manually and we can see that the nom button hides or shows based on 
which cookies I actually baked.

So the goal of a voter is to allow us to centralize that logic so we only have 
it in one spot. I'll create a security directory which is purely for 
organization and then put a cookie voter inside of it. I'm using Symfony 2.6 
for this project which comes with a fantastic new abstract voter class which 
I'm going to use. If you're using Symfony 2.5 or lower you can actually find 
this class on the internet and just use it in your project today. Just update 
the namespace to match your project and then extend it.

This class doesn't have any external dependencies so it's going to work just 
fine in whatever Symfony version you have. So I'll extend it and then use a 
really nice feature in PHPstorm to tell me the three abstract methods that I 
need to fill in. 

So let me back up because I haven't actually told you what these voters do. First 
let me show you how I want our code to look when we're finished. Instead of 
doing the logic manually I'm going to use the `isGranted` function, pass it a 
string: NOM which is something I'm making up -- you'll see why it's important 
in a second -- and then pass the cookie object as the second argument to 
`isGranted`. 

The `isGranted` shortcut is new to 2.6 but all it does is go out to the 
security.context service and call `isGranted` on it. So this is exactly what 
you're using in earlier projects. If you don't have the shortcut method just 
go out to the security.context service manually. Behind the scenes, whenever 
you use the `isGranted` function Symfony calls out to a bunch of voters and 
asks each of them if they can figure out whether or not we should have access. 
For example, whenever you pass `ROLE_SOMETHING` to `isGranted` like `ROLE_USER` 
there's a role of voter class which tries to figure out whether the current 
user has whatever role you're asking about.

What most people don't realize is that you can invent these strings. So in this 
case I'm just inventing nom and we're going to add a new voter into that system 
that says: "Hey Symfony! Whenever the NOM attribute is passed to `isGranted` 
call me!" To get that to work we just need to fill in the `getSupportedClasses` 
and the `getSupportedAttributes` function. 

First in `getSupportedClasses` were going to return the `DeliciousCookie` class 
string. This tells Symfony that when we pass a delicious cookie object to 
`isGranted` our voter should be called. We'll do the same thing in 
`getSupportedAttributes` and we'll return an array with the nom string. This 
tells Symfony that when we pass nom to `isGranted` that *our* voter should be 
called. Whenever both of these functions return true the `isGranted` function 
at the bottom of this class is going to be called. 

For now I'll just use the glorious `var_dump` to print the attribute object and 
user and I'm going to put a die after that. At this point other than the crazy 
debug code I have at the bottom our voter class is ready to go but Symfony is 
not going to automatically find it. To tell Symfony about our new voter we're 
going to need to register it as a service and give it a special tag. I have an 
app/config/services.yml file which I'm importing from my config.yml so we'll put 
the service there. The name doesn't matter but try to keep it relatively short. 
And the autocompleting I'm getting is from the nice Symfony 2 plugin for 
PHPStorm. Our class doesn't have any constructor arguments yet so I'm just 
leaving that key off. The really important part is tags. You need to add one 
tag whose name is security.voter. This is like raising our hand for our voter 
and saying: "Hey Symfony, whenever somebody calls `isGranted` I want *our* voter 
to actually be called." 

So we have the voter, we have the service with the tag so let's try it out. 
When we refresh... Bam! We see things dumped out. Proof that our voter is being 
called. Now here's where things get really really cool. Now in theory because 
of my access control this voter should never be called unless the user is 
logged in. But just in case it is let's use `is_object` to check to see if the 
user is actually logged in. Remember we need to do this because in Symfony 2 if 
you're anonymous the user is actually set to a string. From here it's pure 
business logic, if the Baker's username equals the user's username let's give 
them access. Otherwise let's not give them access. 

So let's refresh the nom request ... and it works! We're logged in as Ryan and 
we are actually nomming a Ryan cookie so this make sense. Remember the goal of 
this was to centralize our logic. So now we can go into our twig template and 
do the exact same thing there. We'll use the `is_granted` function, pass it nom 
and pass it the cookie object. And as you might expect when we refresh me the 
exact same results as before except everything is pulling from that central 
voter. 

Now with everything centralized I want to make things a little bit more 
difficult. In security.yml I've given the Leanna user a special role called 
`ROLE_COOKIE_MONSTER` if you have this role I want to make it so you can eat 
any cookie even if you didn't bake it. Seems like a jerk thing to do but 
let's try it out. To do this, we basically want to call the `isGranted` function 
on the security system from inside of our voter. Now, out-of-the-box we don't 
have access to do this so we're going to need to do a little bit of dependency 
injection. If you're thinking that we'll inject the security context, you're 
basically right. The only issue is that because we're inside of the security 
system if we try to inject the security system into here we're going to get a 
circular dependency. Instead, I'm going to inject the entire container, which, 
yes is typically a bad practice but in this case we can't avoid it and it's not
going to kill us. 

Head back to services.yml add an arguments key now that we have a construct 
function and use `@service_container` to inject the entire container. Back down 
in `isGranted` we can easily add the logic we need. Now I'm using Symfony 2.6 
which gives us a brand-new service called `security.authorization_checker`. 
This is actually a new service for Symfony 2.6. Before it was known as 
security.context. Now don't worry because security.context still exists and will 
still exist until Symfony 3.0. So if you're on Symfony 2.6 use the new service 
name if you're on 2.5 or earlier just use security.context. The nice thing is 
that both of them have the same `isGranted` function on it which we can use now 
to check to see if the user has the `ROLE_COOKIE_MONSTER` role. If they do, let's 
give them access. When we try it out there's no difference and that's a good 
thing. I'm logged in as Ryan so I don't actually have this role. So I'll logout. 
Let's login as Leanna, password cookie, and.....COOKIES FOR EVERYBODY! 

I want to do one more crazy thing. Let's pretend like we want to be able 
to donate our cookies to friends. Now I know that's crazy why would you donate 
cookies to other people but let's just try it out. I don't actually have the 
logic for this but that's okay. Let's go into `index.html.twig' and add a link 
for this. We're just going to see if we can get the link to hide and show 
correctly. Just like before I'm inventing this donate string. If we don't do 
anything else and refresh we'll actually see that the link doesn't show up. If no voters 
vote on our attribute then by default it's going to return false. Now why is
our voter not voting on it? Because of the `getSupportedAttributes` function. 

Let's update that to return true for both the nom and donut...I mean donate. 
Now `isGranted` is going to be handling two different attributes, nom and 
donate. This is the perfect situation for everyone's beloved switch 
case statement. So let's set that up, and we have two cases one for nom and one 
for donate. And the logic for nom is exactly what we had before so I'll just 
copy that, paste that up and if it doesn't get into either those if statements 
we'll return false. 

For the donate case, again, we can do literally anything we want to inside of
this. If we want to go out and make crazy database queries to figure out 
something we can do that. In our case since chocolate cookies are the most 
delicious, let's only give away cookies that aren't chocolate. So, for my crazy 
business logic I'm just going to see if the word chocolate appears in the name 
of the cookie. If it does I'm not going to give it away. But if it doesn't you 
can have it. At the bottom of this function, I still have this false here. This 
should technically never get hit. Even if we pass something other than nom or 
donate to `isGranted` Symfony is not going to call our voter because of the 
`getSupportedAttributes`. 

So, you can put anything down here I like to throw an exception just incase 
something insane happens. But you're going to be fine either way. Cool, let's 
see which cookies we can giveaway. This time we see the donate link only next 
to the cookies that aren't chocolate. That's perfect. Now, some of you may be 
thinking that I'm crazy for having these strings like nom and donate all over 
my application. And actually, I agree with you. Normally whenever I have a 
naked string somewhere I make it a constant instead. So in this case I'll create
two constants: `ATTRIBUTE_NOM` and `ATTRIBUTE_DONATE`. 

Then we can use these inside of `getSupportedAttributes` and later we can use 
it inside of the `isGranted` function. This helps out with typos but it also 
allows us, if we want to, to put some PHP documentation above those constants so 
future us can come and read what nom and donate actually mean.

We can also go into our cookie controller and use the constant there. And yes 
we can also use the constants inside of the twig template with twig's constant 
function but honestly it's kind of ugly so for me I just keep the strings here. 

So security voters are all about solving that case when you need figure out if 
a user has access to do something to a specific object. They help to keep your 
template logic and your controller logic really simple and they're
one of my favorite features so try them out and let me know what you think. 

Symfony also has an ACL system but it's incredibly complex and I only 
recommend that you use it if you have really complex authorization requirements.
If you can somehow write a few lines of code to figure out if a user has
access to do something do that in a voter don't worry about ACL.

Alright see you guys next time! 

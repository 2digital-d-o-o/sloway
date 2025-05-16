<?php
	use Sloway\admin;
	use Sloway\acontrol;

    echo Admin::Field(et("Username"), acontrol::edit("username", $user->username));
    echo Admin::Field(et("E-Mail"), acontrol::edit("email", $user->email));
    echo Admin::Field(et("Password"), acontrol::edit("npassword", "", array("password" => true)));
    echo Admin::Field(et("Confirm Password"), acontrol::edit("cpassword", "", array("password" => true)));

    echo Admin::Field(et("Firstname"), acontrol::edit("firstname", $user->firstname));
    echo Admin::Field(et("Lastname"), acontrol::edit("lastname", $user->lastname));
    echo Admin::Field(et("Company"), acontrol::edit("company", $user->company));
    echo Admin::Field(et("Street"), acontrol::edit("street", $user->street));
    echo Admin::Field(et("Zipcode"), acontrol::edit("zipcode", $user->zipcode));
    echo Admin::Field(et("City"), acontrol::edit("city", $user->city));
    echo Admin::Field(et("Country"), acontrol::select("country", \Sloway\countries::gen("", true), $user->country));

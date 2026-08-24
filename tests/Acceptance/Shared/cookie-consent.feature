Feature:
    I want to make sure that the cookie banner records the decision of the visitor without JavaScript

    Background:
        Given account "MyCompany" with id "666" exists
        Given property "Hotel A" with id "777" of account "666" exists
        Given trip calculation link with id "TL1" and token "token-abc" for property "777" of account "666" exists
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"

    Scenario: Accepting the cookies stores the decision and returns to the same page
        When I send the cookie consent decision "accepted" from "http://localhost/es/carbon-footprint/property-trip/token-abc/trip"
        Then the cookie consent redirects to "http://localhost/es/carbon-footprint/property-trip/token-abc/trip"
        Then the cookie consent value should be "accepted"

    Scenario: Rejecting the cookies stores the decision
        When I send the cookie consent decision "rejected"
        Then the cookie consent value should be "rejected"

    Scenario: An unknown decision is refused
        When I send the cookie consent decision "maybe"
        Then the response status should be 400
        Then there should be no cookie consent value

    Scenario: A referer of another host does not drive the redirection
        When I send the cookie consent decision "accepted" from "https://example.org/phishing"
        Then the cookie consent redirects to "/es/"
        Then the cookie consent value should be "accepted"

    Scenario: The Google fonts of the branding are not requested before accepting
        Given account "666" has branding with primary colour "#8A2432"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the Google fonts of the page are gated behind the consent

    Scenario: The Google fonts of the branding load once the cookies are accepted
        Given account "666" has branding with primary colour "#8A2432"
        When I send the cookie consent decision "accepted"
        When I go to "/es/carbon-footprint/property-trip/token-abc/trip"
        Then the Google fonts of the page load directly

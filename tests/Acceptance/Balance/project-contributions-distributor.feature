Feature:
    A distributor account reviews the contributions made to the projects from its distributed properties

    Background:
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given account "555" has feature "compensation" disabled
        Given user "owner@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given user "user@greenhost.test" with role "ROLE_USER" of account "555" exists
        Given account "Hotel A" with id "1111" exists
        Given account "Hotel B" with id "2222" exists
        Given property "Hotel Playa" with id "11" of account "1111" exists
        Given property "Hotel Rio" with id "21" of account "2222" exists
        Given property "Hotel Lago" with id "22" of account "2222" exists
        Given trip calculation link with id "L21" and token "token-21" for property "21" of account "2222" exists
        Given trip calculation link with id "L22" and token "token-22" for property "22" of account "2222" exists
        Given trip calculation "T21" of link "L21" exists
        Given trip calculation "T22" of link "L22" exists
        Given distributor "555" is assigned to account "1111"
        Given distributor "555" is assigned to property "21"
        Given project "Reforestación" with id "9999" and price per tonne "20" exists
        Given contribution "C11" of account "1111" for project "9999" with "1" tonnes from "property" "11" is pending until "+12 hours"
        Given contribution "C1E" of account "1111" for project "9999" with "1" tonnes from "event_carbon_footprint" "E1" is pending until "+12 hours"
        Given contribution "C21" of account "2222" for project "9999" with "2" tonnes from "property_trip_calculation" "T21" is pending until "+12 hours"
        Given contribution "C22" of account "2222" for project "9999" with "3" tonnes from "property_trip_calculation" "T22" is pending until "+12 hours"

    Scenario: As a distributor owner I see the contributions of my distributed properties
        When I am logged as "owner@greenhost.test"
        When I go to "/es/credits/contributions"
        Then the response status should be 200
        Then I see a text "Propiedad"
        Then I see a text "Hotel A"
        Then I see a link to "/es/credits/contributions/C11"
        Then I see a link to "/es/credits/contributions/C21"
        Then I do not see a link to "/es/credits/contributions/C1E"
        Then I do not see a link to "/es/credits/contributions/C22"

    Scenario: As a distributor owner I filter the contributions by property
        When I am logged as "owner@greenhost.test"
        When I go to "/es/credits/contributions"
        When I filter by "propertyId" with "21"
        Then the response status should be 200
        Then I see a link to "/es/credits/contributions/C21"
        Then I do not see a link to "/es/credits/contributions/C11"

    Scenario: As a distributor owner I see the detail of a distributed contribution without the contributor email
        When I am logged as "owner@greenhost.test"
        When I go to "/es/credits/contributions/C21"
        Then the response status should be 200
        Then I see a text "Desglose económico"
        Then I do not see a text "guest@example.com"

    Scenario: As a distributor owner I cannot see contributions outside my distributed properties
        When I am logged as "owner@greenhost.test"
        When I go to "/es/credits/contributions/C22"
        Then the response status should be 403
        When I go to "/es/credits/contributions/C1E"
        Then the response status should be 403

    Scenario: As a distributor user I do not see the distributed contributions
        When I am logged as "user@greenhost.test"
        When I go to "/es/credits/contributions/C21"
        Then the response status should be 403

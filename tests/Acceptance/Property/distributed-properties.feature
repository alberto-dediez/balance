Feature:
    A distributor account sees its distributed properties in its properties list

    Background:
        Given account "Greenhost" with id "555" exists
        Given account "555" is a distributor
        Given user "owner@greenhost.test" with role "ROLE_OWNER" of account "555" exists
        Given account "Hotel A" with id "1111" exists
        Given account "Hotel B" with id "2222" exists
        Given account "Hotel C" with id "3333" exists
        Given account "3333" has feature "property_trip_calculators" disabled
        Given user "owner@hotelb.test" with role "ROLE_OWNER" of account "2222" exists
        Given property "Sede Greenhost" with id "50" of account "555" exists
        Given property "Hotel Playa" with id "11" of account "1111" exists
        Given property "Hotel Sierra" with id "12" of account "1111" exists
        Given property "Hotel Rio" with id "21" of account "2222" exists
        Given property "Hotel Lago" with id "22" of account "2222" exists
        Given property "Hotel Nube" with id "31" of account "3333" exists
        Given distributor "555" is assigned to account "1111"
        Given distributor "555" is assigned to property "21"
        Given distributor "555" is assigned to account "3333"

    Scenario: As a distributor owner I see my own and my distributed properties with their account
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties"
        Then the response status should be 200
        Then I see a text "Cuenta"
        Then I see a text "Sede Greenhost"
        Then I see a text "Hotel Playa"
        Then I see a text "Hotel Sierra"
        Then I see a text "Hotel Rio"
        Then I see a text "Hotel A"
        Then I do not see a text "Hotel Lago"
        Then I do not see a text "Hotel Nube"
        Then I see a link to "/es/properties/edit/50"
        Then I see a link to "/es/carbon-footprint/property-trip-calculations/11/responses"
        Then I see a link to "/es/carbon-footprint/property-trip-calculations/21/responses"
        Then I do not see a link to "/es/properties/edit/11"
        Then I do not see a link to "/es/carbon-footprint/property-trip-calculations/11"
        Then I do not see a link to "/es/property-carbon-footprints/register?propertyId=11"

    Scenario: As a distributor owner I filter my properties and reload the list
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties"
        When I filter by "name" with "Hotel"
        Then the response status should be 200
        When I go to "/es/properties"
        Then the response status should be 200
        Then I see a text "Hotel Playa"
        Then I see a text "Hotel Rio"
        Then I do not see a text "Sede Greenhost"

    Scenario: A new property of a distributed account appears in the distributor list
        Given property "Hotel Nuevo" with id "13" of account "1111" exists
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties"
        Then I see a text "Hotel Nuevo"

    Scenario: A distributed property disappears when its assignment is removed
        When I am logged as "owner@hotelb.test"
        When I go to "/es/distributors"
        Then the response status should be 200
        When I click on the action "Eliminar"
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties"
        Then I do not see a link to "/es/carbon-footprint/property-trip-calculations/21/responses"
        Then I see a link to "/es/carbon-footprint/property-trip-calculations/11/responses"

    Scenario: As a distributor owner I see the responses of a distributed property without compensation nor export
        When I am logged as "owner@greenhost.test"
        When I go to "/es/carbon-footprint/property-trip-calculations/11/responses"
        Then the response status should be 200
        Then I see a text "Hotel Playa"
        Then I do not see a text "Compensar emisiones"
        Then I do not see a text "property_trip_calculations"

    Scenario: As a distributor owner I can not see the responses of a not distributed property
        When I am logged as "owner@greenhost.test"
        When I go to "/es/carbon-footprint/property-trip-calculations/22/responses"
        Then the response status should be 403

    Scenario: As a distributor owner I can not see the responses of a hotel without trip calculators
        When I am logged as "owner@greenhost.test"
        When I go to "/es/carbon-footprint/property-trip-calculations/31/responses"
        Then the response status should be 403

    Scenario: As a distributor owner I can not edit a distributed property
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties/edit/11"
        Then the response status should be 403

    Scenario: As a distributor owner I can not remove a distributed property
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties/remove/11" with csrf token "remove"
        Then the response status should be 403

    Scenario: As a distributor owner I can not open the calculator of a distributed property
        When I am logged as "owner@greenhost.test"
        When I go to "/es/carbon-footprint/property-trip-calculations/11"
        Then the response status should be 403

    Scenario: A distributor without property features sees the properties menu without the register button
        Given account "555" has feature "property_trip_calculators" disabled
        Given account "555" has feature "events" disabled
        Given account "555" has feature "organizational_carbon_footprint" disabled
        When I am logged as "owner@greenhost.test"
        When I go to "/es/properties"
        Then the response status should be 200
        Then I see a link to "/es/properties"
        Then I see a text "Hotel Playa"
        Then I do not see a link to "/es/properties/register"
        Then I do not see a link to "/es/companies"
        Then I do not see a link to "/es/carbon-footprint/property-trip-calculations/11/responses"

    Scenario: An account that is not a distributor does not see the account column
        When I am logged as "owner@hotelb.test"
        When I go to "/es/properties"
        Then the response status should be 200
        Then I see a text "Hotel Rio"
        Then I do not see a text "Hotel Playa"
        Then I do not see a text "Sede Greenhost"
        Then I see a link to "/es/properties/register"

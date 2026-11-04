Feature:
    I want the owner menu to show only the features contracted by the current account

    Background:
        Given account "Acme" with id "a1" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "a1" exists

    Scenario: As an owner with every feature I see all the feature entries
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then the response status should be 200
        Then I see a link to "/es/events"
        Then I see a link to "/es/carbon-footprint/trip-calculator"
        Then I see a link to "/es/projects"
        Then I see a link to "/es/my-balance"
        Then I see a link to "/es/property-carbon-footprints"
        Then I see a link to "/es/properties"
        Then I see a link to "/es/companies"
        Then I see a link to "/es/providers"

    Scenario: As an owner without events I do not see the events entry
        Given account "a1" has feature "events" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I do not see a link to "/es/events"
        Then I see a link to "/es/providers"

    Scenario: As an owner without the account trip calculator I do not see the trip calculator entry
        Given account "a1" has feature "account_trip_calculator" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I do not see a link to "/es/carbon-footprint/trip-calculator"
        Then I do not see a link to "/es/carbon-footprint/trip-calculator/responses"

    Scenario: As an owner without compensation I do not see the projects entries
        Given account "a1" has feature "compensation" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I do not see a link to "/es/projects"
        Then I do not see a link to "/es/credits"
        Then I do not see a link to "/es/credits/contributions"
        Then I do not see a link to "/es/my-balance"
        Then I do not see a link to "/es/my-payee"

    Scenario: As an owner without the organizational carbon footprint I do not see the footprints entries
        Given account "a1" has feature "organizational_carbon_footprint" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I do not see a link to "/es/property-carbon-footprints"
        Then I do not see a link to "/es/company-carbon-footprints"
        Then I see a link to "/es/properties"
        Then I see a link to "/es/companies"

    Scenario: As an owner with only the property trip calculators I see properties and companies but not providers
        Given account "a1" has feature "events" disabled
        Given account "a1" has feature "organizational_carbon_footprint" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I see a link to "/es/properties"
        Then I see a link to "/es/companies"
        Then I do not see a link to "/es/providers"

    Scenario: As an owner with only the organizational carbon footprint I see properties, companies and providers
        Given account "a1" has feature "events" disabled
        Given account "a1" has feature "property_trip_calculators" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I see a link to "/es/properties"
        Then I see a link to "/es/companies"
        Then I see a link to "/es/providers"

    Scenario: As an owner with only events I see properties, companies and providers
        Given account "a1" has feature "organizational_carbon_footprint" disabled
        Given account "a1" has feature "property_trip_calculators" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I see a link to "/es/properties"
        Then I see a link to "/es/companies"
        Then I see a link to "/es/providers"

    Scenario: As an owner without events, organizational carbon footprint nor property trip calculators I do not see properties, companies nor providers
        Given account "a1" has feature "events" disabled
        Given account "a1" has feature "organizational_carbon_footprint" disabled
        Given account "a1" has feature "property_trip_calculators" disabled
        When I am logged as "owner@dediez.software"
        When I go to "/es/users"
        Then I do not see a link to "/es/properties"
        Then I do not see a link to "/es/companies"
        Then I do not see a link to "/es/providers"

    Scenario: As owner of a consultant account I see the menu of the impersonated account
        Given account "ConsultantAccount" with id "consultant-account-id" is a consultant
        Given account "AcmeAccount" with id "acme-account-id" exists
        Given account "acme-account-id" has consultant "consultant-account-id"
        Given account "acme-account-id" has feature "events" disabled
        Given user "consultant@dediez.software" with role "ROLE_OWNER" of account "consultant-account-id" exists
        When I am logged as "consultant@dediez.software"
        When I go to "/es/users"
        Then I see a link to "/es/events"
        When I impersonate account "AcmeAccount"
        When I go to "/es/users"
        Then I do not see a link to "/es/events"

    Scenario: As admin I see the menu of the impersonated account
        Given account "AdminAccount" with id "admin-account-id" exists
        Given account "AcmeAccount" with id "acme-account-id" exists
        Given account "acme-account-id" has feature "events" disabled
        Given user "admin@dediez.software" with role "ROLE_ADMIN" of account "admin-account-id" exists
        When I am logged as "admin@dediez.software"
        When I impersonate account "AcmeAccount"
        When I go to "/es/users"
        Then the response status should be 200
        Then I do not see a link to "/es/events"
        Then I see a link to "/es/providers"

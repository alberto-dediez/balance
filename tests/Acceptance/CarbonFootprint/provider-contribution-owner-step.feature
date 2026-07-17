Feature:
    I want to make sure that the owner can assign providers to a carbon footprint from the wizard's "Proveedores" step

    Scenario: As an owner, I can see the providers step once breakdown has been answered
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/providers"
        Then the response status should be 200
        Then I see a text "Proveedor A"

    Scenario: As an owner, I am redirected to breakdown if it has not been answered yet
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint for event "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/providers"
        Then I am redirected to route "events_carbon_footprint_breakdown"

    Scenario: As an owner, I can assign a provider and a pending contribution is registered
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        Given event carbon footprint with id "999" for event "333" of account "666" with breakdown answered exists
        Given provider "Proveedor A" with id "444" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/providers"
        When I submit a form with "[providerIds][0]:444" and button "Guardar y continuar"
        Then I am redirected to route "events_carbon_footprint_employees"

    Scenario: As a user, I can't access the providers step
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given event "Evento A" with id "333" exists
        Given event carbon footprint with id "999" for event "333" of account "account-id" with breakdown answered exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/providers"
        Then the response status should be 403

    Scenario: As an owner, I can't access the providers step of another account's event
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento Ajeno" with id "333" of account "777" exists
        Given event carbon footprint with id "999" for event "333" of account "777" with breakdown answered exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/step/providers"
        Then the response status should be 403

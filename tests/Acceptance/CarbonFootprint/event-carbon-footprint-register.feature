Feature:
    I want to make sure that the software allows to create a carbon footprint calculation from an event

    Scenario: As an owner, I can create a carbon footprint calculation for my own event
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento A" with id "333" of account "666" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/register"
        When I follow redirect
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Cálculo de Huella de Carbono creado correctamente"

    Scenario: As an owner, I can't create a carbon footprint for events from other accounts
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given event "Evento Ajeno" with id "333" of account "777" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/events/333/carbon-footprint/register"
        Then the response status should be 403

    Scenario: As a user I can't create a carbon footprint
        Given user "user1@dediez.software" with role "ROLE_USER" exists
        Given event "Evento A" with id "333" exists
        When I am logged as "user1@dediez.software"
        When I go to "/es/events/333/carbon-footprint/register"
        Then the response status should be 403

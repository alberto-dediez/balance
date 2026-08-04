Feature:
    Ability for owners to cancel their own pending CO2 credit purchase requests

    Scenario: As an owner I can cancel my own pending request
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases/2222/cancel"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud de créditos cancelada"

    Scenario: As an owner I can't cancel another account's pending request
        Given account "MyCompany" with id "666" exists
        Given account "OtherCompany" with id "777" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "777" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases/2222/cancel"
        Then the response status should be 403

Feature:
    Ability for owners to request the purchase of CO2 credits for a compensation project

    Scenario: As an owner I can request credits for a compensation project
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/projects/1111/credits/request"
        When I submit a form with "tonnes:10" and button "Solicitar créditos"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud de créditos enviada correctamente"

    Scenario: As an owner I can cancel a pending request
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/credits/purchases/2222/cancel"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud de créditos cancelada"

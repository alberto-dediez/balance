Feature:
    Ability for admins to validate pending CO2 credit purchase requests

    Scenario: As an admin I can validate a request in full
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/validate"
        When I submit a form with "purchasedTonnes:10|amount:150|reference:REF-1" and button "Validar compra"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Compra validada correctamente"

    Scenario: As an admin I can validate a request partially when there is not enough availability
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/validate"
        When I submit a form with "purchasedTonnes:6|amount:90|reference:REF-2" and button "Validar compra"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Compra validada parcialmente"

    Scenario: As an owner I can't validate a request
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/credits/2222/validate"
        Then the response status should be 403

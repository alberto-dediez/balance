Feature:
    Ability for admins to reject pending CO2 credit purchase requests

    Scenario: As an admin I can reject a request
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/credits/2222/reject"
        When I submit a form with "reason:Sin disponibilidad" and button "Rechazar solicitud"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Solicitud rechazada correctamente"

    Scenario: As an owner I can't reject a request
        Given account "MyCompany" with id "666" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "666" exists
        Given project "Item 1" with id "1111" exists
        Given credit purchase "2222" for account "666" and project "1111" requesting "10" tonnes exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/credits/2222/reject"
        Then the response status should be 403

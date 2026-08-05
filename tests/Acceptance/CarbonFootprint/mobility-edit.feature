Feature:
    I want to make sure that the system is allow to access the details of a mobility

    Scenario: As an admin, I can access to edit mobilities
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mobility "Movility 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mobilities/edit/333"
        When I submit a form with "name:A new name|unit:ud" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Movilidad actualizada con éxito"

    Scenario: As an admin, I can set the travel mode of a mobility
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mobility "Coche" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mobilities/edit/333"
        When I submit a form with "name:Coche|unit:km|travelMode:driving" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then mobility "333" has travel mode "driving"

    Scenario: As an admin, I can clear the travel mode of a mobility
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mobility "Coche" with id "333" and travel mode "driving" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mobilities/edit/333"
        When I submit a form with "name:Coche|unit:km|travelMode:" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then mobility "333" has no travel mode

    Scenario: As an admin, I see the emission factors table when editing a mobility
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given mobility "Movility 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/mobilities/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"

    Scenario: As an owner, I can't access to edit mobilities
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given mobility "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/mobilities/edit/333"
        Then the response status should be 403

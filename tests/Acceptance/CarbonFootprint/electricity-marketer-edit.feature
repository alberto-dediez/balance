Feature:
    I want to make sure that the system is allow to access the details of an electricity marketer

    Scenario: As an admin, I can access to edit electricity marketers
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/edit/333"
        When I submit a form with "name:A new name" and button "Guardar y salir"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Comercializadora eléctrica actualizada con éxito"

    Scenario: As an admin, I see the emission factors table when editing an electricity marketer
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given electricity marketer "Marketer 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/electricity-marketers/edit/333"
        Then the response status should be 200
        Then I see a text "Factores de emisión"
        Then I see a text "Well-to-tank"

    Scenario: As an owner, I can't access to edit electricity marketers
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given electricity marketer "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/electricity-marketers/edit/333"
        Then the response status should be 403

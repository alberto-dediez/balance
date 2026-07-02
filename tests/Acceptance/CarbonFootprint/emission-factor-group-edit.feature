Feature:
    I want to make sure that the system is allow to access the details of an emission factor group

    Scenario: As an admin, I can access to edit emission factor groups
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given emission factor group "Group 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/emission-factor-groups/edit/333"
        When I submit a form with "name:A new name|gwpCo2:1|gwpCh4:27.9|gwpN2O:273" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Grupo de coeficientes actualizado con éxito"

    Scenario: As an owner, I can't access to edit emission factor groups
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given emission factor group "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/emission-factor-groups/edit/333"
        Then the response status should be 403

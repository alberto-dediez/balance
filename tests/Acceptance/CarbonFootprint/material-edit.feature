Feature:
    I want to make sure that the system is allow to access the details of a material

    Scenario: As an admin, I can access to edit materials
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given material "Material 1" with id "333" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/materials/edit/333"
        When I submit a form with "name:A new name|category:glass|unit:ud" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Material actualizado con éxito"

    Scenario: As an owner, I can't access to edit materials
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given material "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/materials/edit/333"
        Then the response status should be 403

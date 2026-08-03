Feature:
    I want to make sure that the system is allow to access the details of a project

    Scenario: As an admin, I can access to edit projects
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given destination "Madrid" with id "abc123" exists
        Given project "Item 1" with id "333" and location "abc123" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/projects/edit/333"
        When I submit a form with "name:A new name|description:A new description|location:abc123|projectType:social_project|projectStatus:in_progress" and button "Guardar"
        When I follow redirect
        Then the response status should be 200
        Then I see a text "Proyecto actualizado con éxito"

    Scenario: As an owner, I can't access to edit projects
        Given account "MyCompany" with id "mycompany" exists
        Given user "owner@dediez.software" with role "ROLE_OWNER" of account "mycompany" exists
        Given project "An item" with id "333" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/projects/edit/333"
        Then the response status should be 403

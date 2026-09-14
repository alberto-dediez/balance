Feature:
    Ability to list changelog entries in web

    Scenario: As admin I see the published entries
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "primera-entrada" titled "Primera entrada" in category "feature" exists
        Given changelog entry "segunda-entrada" titled "Segunda entrada" in category "fix" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog"
        Then the response status should be 200
        Then I see a table with 3 rows

    Scenario: Unpublished entries do not appear in the list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "publicada" titled "Publicada" in category "feature" exists
        Given changelog entry "borrador" is not published
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog"
        Then the response status should be 200
        Then I do not see a text "Entrada sin publicar"

    Scenario: Filtering by category shows only that category
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "una-mejora" titled "Una mejora" in category "improvement" exists
        Given changelog entry "una-correccion" titled "Una corrección" in category "fix" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog"
        When I filter by "category" with "improvement"
        Then the response status should be 200
        Then I see a text "Una mejora"
        Then I do not see a text "Una corrección"

    Scenario: Filtering by text finds an entry by its title
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "firma-electronica" titled "Firma electrónica" in category "feature" exists
        Given changelog entry "otra-cosa" titled "Otra cosa" in category "fix" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog"
        When I filter by "text" with "firma"
        Then the response status should be 200
        Then I see a text "Firma electrónica"
        Then I do not see a text "Otra cosa"

    Scenario: Filtering by text finds an entry by its summary
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "firma-electronica" titled "Firma electrónica" in category "feature" exists
        Given changelog entry "otra-cosa" titled "Otra cosa" in category "fix" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog"
        When I filter by "text" with "electrónica summary"
        Then the response status should be 200
        Then I see a text "Firma electrónica"
        Then I do not see a text "Otra cosa"

    Scenario: The filter is kept when coming back to the list
        Given user "admin@dediez.software" with role "ROLE_ADMIN" exists
        Given changelog entry "una-mejora" titled "Una mejora" in category "improvement" exists
        Given changelog entry "una-correccion" titled "Una corrección" in category "fix" exists
        When I am logged as "admin@dediez.software"
        When I go to "/es/admin/changelog"
        When I filter by "category" with "improvement"
        When I go to "/es/admin/changelog"
        Then the response status should be 200
        Then I see a text "Una mejora"
        Then I do not see a text "Una corrección"

    Scenario: As owner I cannot access the changelog list
        Given user "owner@dediez.software" with role "ROLE_OWNER" exists
        When I am logged as "owner@dediez.software"
        When I go to "/es/admin/changelog"
        Then the response status should be 403

    Scenario: As user I cannot access the changelog list
        Given user "user@dediez.software" with role "ROLE_USER" exists
        When I am logged as "user@dediez.software"
        When I go to "/es/admin/changelog"
        Then the response status should be 403

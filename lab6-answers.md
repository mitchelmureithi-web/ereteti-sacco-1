# Lab 6 -- my answers
Name: Mitchell Kendi
Admission Number: 226634

Q1. Php checks the member number again because eventhough the browser checks the number first, a user can easily turn off or bypass those checks on their own computer by using developer tools like Inspect Element, by right clicking the form on the browser, opening html code, and deleting rules like required or type="number" before clicking submit. Having PHP check it again on the server makes sure no invalid data gets saved to the system

Q2. i- integer, binds the farmer ID
    s- string, binds the delivery date
    s- string, binds morning/evening session
    d- decimal, binds litres value

Q3. Delete uses POST form because deleting data changes the database, the link should only be used to view pages, like an edit screen without changing any information. The actual update is then sent using POST

Q4. The website treats ' OR '1'='1' as plain text and looks for a delivery date with the exact same name, returning no results. Bacause a prepared statement locks the SQL query strusture before adding the users input, the database treats the input strictly as a search value reather than executable code. The date value is also constrained to a valid date by PHP or the form, so it should produce no matching rows
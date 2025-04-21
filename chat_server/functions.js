export function assignQueryToExecutive(executivesMap, query) {
    const sortedExecutives = Array.from(executivesMap.entries()).sort(([, a], [, b]) => Number(a.preference) - Number(b.preference));
    for (const [key, exec] of sortedExecutives) {
        if (exec.totalAssigned < exec.capacity) {
            exec.assignedQueries.push(query);
            exec.totalAssigned += 1;
            executivesMap.set(key, exec);
            // console.log(`✅ Query assigned to ${exec.name}`);
            return exec;
        }
    }
    // console.log("❌ No executive available to handle the query.");
    return null;
}